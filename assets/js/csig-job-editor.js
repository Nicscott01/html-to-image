document.addEventListener('DOMContentLoaded', function() {
    const runButton = document.getElementById('csig-run-job');
    
    if (!runButton) {
        console.log('CSIG: Run button not found');
        return;
    }

    function appendGeneratedFileToList(url, format) {
        const container = document.getElementById('csig-generated-files-container');
        if (!container) {
            return;
        }

        const deleteAllButton = container.querySelector('.csig-delete-all-files');
        if (deleteAllButton) {
            deleteAllButton.disabled = false;
            if (deleteAllButton.dataset && deleteAllButton.dataset.label) {
                deleteAllButton.textContent = deleteAllButton.dataset.label;
            }
        }

        let list = container.querySelector('ul.csig-generated-files');
        if (!list) {
            // Remove empty notice if present
            const emptyNotice = container.querySelector('em');
            if (emptyNotice) {
                emptyNotice.remove();
            }

            list = document.createElement('ul');
            list.className = 'csig-generated-files';
            list.style.margin = '5px 0 0 0';
            list.style.paddingLeft = '15px';
            list.style.fontSize = '12px';
            container.appendChild(list);
        }

        const li = document.createElement('li');
        li.dataset.fileUrl = url;
        li.style.marginBottom = '6px';

        const filename = url.split('/').pop();
        const now = new Date();
        const meta = `${format.toUpperCase()} · ${now.toLocaleString()}`;

        li.innerHTML = `
            <div>
                <a href="${url}" target="_blank">${filename}</a>
                <span style="color: #666;">&mdash; ${meta}</span>
            </div>
            <button type="button" class="button-link-delete csig-delete-file" style="color: #b32d2e;">&times; ${csigJobData.i18nDelete || 'Delete'}</button>
        `;

        list.insertBefore(li, list.firstChild);
    }

    // Helper function to sanitize filename from various sources
    function sanitizeFilename(filename) {
        if (!filename) {
            return null;
        }
        
        // If it looks like an email address, convert it
        if (filename.includes('@')) {
            // Convert email to filename: john.doe@company.com -> john-doe-at-company-com
            filename = filename
                .replace('@', '-at-')
                .replace(/\./g, '-')
                .toLowerCase();
        }
        
        // General filename sanitization
        return filename
            .replace(/[^a-zA-Z0-9\-_]/g, '-') // Replace special chars with dashes
            .replace(/--+/g, '-') // Replace multiple dashes with single dash
            .replace(/^-+|-+$/g, '') // Remove leading/trailing dashes
            .toLowerCase();
    }
    
    // Helper function to load Google Fonts into the iframe
    async function loadGoogleFonts(iframeDoc) {
        console.log('CSIG: Loading Google Fonts...');
        
        // Find all Google Font stylesheets
        const googleFontLinks = Array.from(iframeDoc.querySelectorAll('link[href*="fonts.googleapis.com"]'));
        
        if (googleFontLinks.length === 0) {
            console.log('CSIG: No Google Fonts found');
            return;
        }
        
        console.log('CSIG: Found', googleFontLinks.length, 'Google Font links');
        
        for (const link of googleFontLinks) {
            try {
                console.log('CSIG: Processing font:', link.href);
                
                // Fetch the CSS from Google Fonts
                const response = await fetch(link.href);
                const cssText = await response.text();
                
                console.log('CSIG: Downloaded CSS, extracting font URLs...');
                
                // Extract font URLs from the CSS
                const fontUrls = [];
                const fontUrlRegex = /url\((https:\/\/fonts\.gstatic\.com[^)]+)\)/g;
                let match;
                
                while ((match = fontUrlRegex.exec(cssText)) !== null) {
                    fontUrls.push(match[1]);
                }
                
                console.log('CSIG: Found', fontUrls.length, 'font files to download');
                
                // Download each font file and convert to data URL
                let updatedCss = cssText;
                for (const fontUrl of fontUrls) {
                    try {
                        console.log('CSIG: Downloading font:', fontUrl);
                        const fontResponse = await fetch(fontUrl);
                        const fontBlob = await fontResponse.blob();
                        const fontDataUrl = await new Promise(resolve => {
                            const reader = new FileReader();
                            reader.onload = () => resolve(reader.result);
                            reader.readAsDataURL(fontBlob);
                        });
                        
                        // Replace the URL in the CSS with the data URL
                        updatedCss = updatedCss.replace(fontUrl, fontDataUrl);
                        console.log('CSIG: Font converted to data URL');
                    } catch (fontError) {
                        console.warn('CSIG: Failed to download font:', fontUrl, fontError);
                    }
                }
                
                // Create a new style element with the updated CSS
                const styleElement = iframeDoc.createElement('style');
                styleElement.textContent = updatedCss;
                
                // Remove the original link and add our style
                link.remove();
                iframeDoc.head.appendChild(styleElement);
                
                console.log('CSIG: Font stylesheet replaced with inline version');
                
            } catch (error) {
                console.warn('CSIG: Failed to process font link:', link.href, error);
            }
        }
        
        // Wait a bit for fonts to be applied
        await new Promise(resolve => setTimeout(resolve, 2000));
        console.log('CSIG: Font loading complete');
    }
    
    runButton.addEventListener('click', async function() {
        console.log('CSIG: Starting job execution');
        
        const url = csigJobData.jobUrl;
        const settings = csigJobData.settings;
        const format = settings.format || 'raster';
        
        // Use existing iframe instead of creating new one
        const iframe = window.csigCurrentIframe ? window.csigCurrentIframe() : null;
        
        if (!iframe) {
            alert('Preview iframe not ready. Please wait for the preview to load.');
            return;
        }
        
        if (!url) {
            alert('Please add a URL to this job before running it.');
            return;
        }

        // Show status and disable button
        const statusDiv = document.getElementById('csig-status');
        const statusText = document.getElementById('csig-status-text');
        const progressBar = document.getElementById('csig-progress-bar');
        const resultsDiv = document.getElementById('csig-results');
        const fileList = document.getElementById('csig-file-list');
        
        runButton.disabled = true;
        runButton.textContent = 'Processing...';
        statusDiv.style.display = 'block';
        resultsDiv.style.display = 'none';
        statusText.textContent = 'Processing fonts and elements...';
        progressBar.style.width = '15%';

        const generatedFiles = [];

        try {
            console.log('CSIG: Using existing iframe for capture');
            
            // Load and inline Google Fonts
            progressBar.style.width = '20%';
            statusText.textContent = 'Loading and inlining fonts...';
            await loadGoogleFonts(iframe.contentDocument);

            // Find elements with the selector
            const selector = settings.selector || '.csig-card';
            const elements = iframe.contentDocument.querySelectorAll(selector);
            
            console.log('CSIG: Found elements:', elements.length, 'with selector:', selector);
            
            if (elements.length === 0) {
                throw new Error(`No elements found with selector "${selector}"`);
            }

            statusText.textContent = `Found ${elements.length} element(s) with selector "${selector}". Loading images...`;
            progressBar.style.width = '30%';

            // Wait for images to load with timeout (longer timeout for retina support)
            const isRetina = settings.retinaSupport || settings.pixelRatio > 2;
            const imageTimeout = isRetina ? 8000 : 4000; // Per image timeout
            const totalTimeout = isRetina ? 20000 : 10000; // Total timeout
            
            await new Promise(resolve => {
                const images = iframe.contentDocument.querySelectorAll('img');
                let loadedCount = 0;
                const totalImages = images.length;
                let timeoutId;
                
                console.log('CSIG: Found', totalImages, 'images to load (retina:', isRetina, ', pixel ratio:', settings.pixelRatio, ')');

                // Force reload all images with cache busting for consistent rendering
                const timestamp = Date.now();
                images.forEach((img, index) => {
                    const originalSrc = img.src;
                    // Add timestamp to force reload, but preserve query params
                    const separator = originalSrc.includes('?') ? '&' : '?';
                    const newSrc = `${originalSrc}${separator}_csig_cb=${timestamp}&_img=${index}`;
                    
                    console.log(`CSIG: Force reloading image ${index}: ${originalSrc} -> ${newSrc}`);
                    img.src = newSrc;
                });

                const finish = () => {
                    if (timeoutId) {
                        clearTimeout(timeoutId);
                    }
                    console.log('CSIG: Image loading complete (loaded:', loadedCount, '/', totalImages, ')');
                    const finalWait = isRetina ? 3000 : 2000; // Extra wait time after force reload
                    setTimeout(resolve, finalWait);
                };

                if (totalImages === 0) {
                    console.log('CSIG: No images found, waiting for final rendering');
                    const finalWait = isRetina ? 2000 : 1000;
                    setTimeout(resolve, finalWait);
                    return;
                }

                timeoutId = setTimeout(() => {
                    console.log('CSIG: Image loading timeout reached, proceeding anyway');
                    finish();
                }, totalTimeout);

                const checkLoaded = (imageIndex, status) => {
                    loadedCount++;
                    console.log(`CSIG: Image ${imageIndex} ${status}, count:`, loadedCount, '/', totalImages);
                    if (loadedCount >= totalImages) {
                        finish();
                    }
                };

                images.forEach((img, index) => {
                    if (img.complete) {
                        checkLoaded(index, 'already loaded');
                    } else {
                        const loadHandler = () => checkLoaded(index, 'loaded');
                        const errorHandler = () => checkLoaded(index, 'failed');
                        
                        img.addEventListener('load', loadHandler, { once: true });
                        img.addEventListener('error', errorHandler, { once: true });
                        
                        setTimeout(() => {
                            if (!img.complete) {
                                img.removeEventListener('load', loadHandler);
                                img.removeEventListener('error', errorHandler);
                                checkLoaded(index, 'timeout');
                            }
                        }, imageTimeout);
                    }
                });
            });

            console.log('CSIG: All assets loaded, starting image generation...');
            progressBar.style.width = '50%';
            statusText.textContent = 'Generating images...';

            // Process each element
            for (let i = 0; i < elements.length; i++) {
                const element = elements[i];
                const elementProgress = 50 + (40 * (i / elements.length));
                progressBar.style.width = elementProgress + '%';
                statusText.textContent = `Processing element ${i + 1} of ${elements.length}...`;
                
                console.log('CSIG: Processing element', i + 1, '/', elements.length);
                
                // Debug: Check element dimensions and content
                const rect = element.getBoundingClientRect();
                console.log('CSIG: Element details:', {
                    index: i,
                    tagName: element.tagName,
                    className: element.className,
                    id: element.id,
                    width: rect.width,
                    height: rect.height,
                    offsetWidth: element.offsetWidth,
                    offsetHeight: element.offsetHeight,
                    childElementCount: element.childElementCount,
                    hasImages: element.querySelectorAll('img').length
                });
                
                // Check if element is visible
                const computedStyle = iframe.contentWindow.getComputedStyle(element);
                if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden' || computedStyle.opacity === '0') {
                    console.warn('CSIG: Element is not visible, skipping:', {
                        display: computedStyle.display,
                        visibility: computedStyle.visibility,
                        opacity: computedStyle.opacity
                    });
                    continue;
                }
                
                // Look for custom filename attribute
                const customFilename = element.getAttribute('csig-filename') || 
                                     element.getAttribute('data-csig-filename') ||
                                     element.getAttribute('data-filename');
                
                const sanitizedFilename = sanitizeFilename(customFilename);
                
                console.log('CSIG: Custom filename found:', customFilename, '-> sanitized:', sanitizedFilename);
                
                // Clear any selections or focus that might interfere with capture
                try {
                    if (iframe.contentWindow.getSelection) {
                        iframe.contentWindow.getSelection().removeAllRanges();
                    }
                    if (iframe.contentDocument.activeElement && iframe.contentDocument.activeElement.blur) {
                        iframe.contentDocument.activeElement.blur();
                    }
                } catch (clearError) {
                    console.log('CSIG: Could not clear selections:', clearError);
                }

                // Generate PNG if needed
                if (format === 'raster' || format === 'both') {
                    try {
                        console.log('CSIG: Generating PNG for element', i + 1, '(pixel ratio:', settings.pixelRatio, ')');
                        
                        let pngData;
                        let actualPixelRatio = settings.pixelRatio;
                        
                        // Progressive fallback for high pixel ratios
                        try {
                            // First attempt with requested pixel ratio
                            const renderOptions = {
                                quality: parseFloat(settings.imageQuality) === 'high' ? 0.95 : parseFloat(settings.imageQuality) === 'ultra' ? 1 : 0.8,
                                pixelRatio: actualPixelRatio,
                                useCORS: true,
                                allowTaint: true,
                                skipFonts: false,
                                cacheBust: true,
                                backgroundColor: '#ffffff',
                                // Force fresh render by adding timestamp
                                fetchRequestInit: {
                                    cache: 'no-cache'
                                }
                            };
                            
                            pngData = await htmlToImage.toPng(element, renderOptions);
                        } catch (highResError) {
                            console.warn('CSIG: High resolution failed, trying lower pixel ratio:', highResError);
                            
                            // If retina/high pixel ratio fails, try with reduced pixel ratio
                            if (actualPixelRatio > 2) {
                                actualPixelRatio = 2;
                                console.log('CSIG: Retrying with pixel ratio:', actualPixelRatio);
                                
                                try {
                                    pngData = await htmlToImage.toPng(element, {
                                        quality: 0.9,
                                        pixelRatio: actualPixelRatio,
                                        useCORS: true,
                                        allowTaint: true,
                                        skipFonts: false,
                                        cacheBust: true,
                                        backgroundColor: '#ffffff',
                                        fetchRequestInit: {
                                            cache: 'no-cache'
                                        }
                                    });
                                } catch (mediumResError) {
                                    console.warn('CSIG: Medium resolution failed, trying pixel ratio 1:', mediumResError);
                                    
                                    // Final fallback to pixel ratio 1
                                    actualPixelRatio = 1;
                                    pngData = await htmlToImage.toPng(element, {
                                        quality: 0.8,
                                        pixelRatio: actualPixelRatio,
                                        useCORS: false,
                                        allowTaint: false,
                                        skipFonts: true,
                                        cacheBust: true, // Keep cache busting even in fallback
                                        backgroundColor: '#ffffff',
                                        fetchRequestInit: {
                                            cache: 'no-cache'
                                        }
                                    });
                                }
                            } else {
                                // Re-throw if already at low pixel ratio
                                throw highResError;
                            }
                        }
                        
                        console.log('CSIG: PNG generated successfully with pixel ratio:', actualPixelRatio);

                    const formData = new FormData();
                    formData.append('action', 'csig_save_image');
                    formData.append('nonce', csigJobData.nonce);
                    formData.append('image_data', pngData);
                    formData.append('element_index', i);
                    formData.append('job_id', csigJobData.jobId);
                    formData.append('overwrite_files', settings.overwriteFiles ? '1' : '0');

                    if (sanitizedFilename) {
                        formData.append('custom_filename', sanitizedFilename);
                    }

                    const pngResponse = await fetch(csigJobData.ajaxUrl, {
                        method: 'POST',
                        body: formData
                    });

                        const pngResult = await pngResponse.json();
                        console.log('CSIG: PNG save result:', pngResult);
                        
                        if (pngResult.success) {
                            generatedFiles.push(pngResult.data.url);
                            appendGeneratedFileToList(pngResult.data.url, 'png');
                        } else {
                            console.error('CSIG: PNG save failed:', pngResult);
                        }
                    } catch (error) {
                        console.error('CSIG: PNG generation failed for element', i + 1, ':', error);
                        console.error('CSIG: Failed element details:', {
                            tagName: element.tagName,
                            className: element.className,
                            id: element.id,
                            offsetWidth: element.offsetWidth,
                            offsetHeight: element.offsetHeight,
                            pixelRatio: settings.pixelRatio,
                            retinaSupport: settings.retinaSupport
                        });
                    }
                }

                // Generate PDF if needed
                if (format === 'vector' || format === 'both') {
                    try {
                        console.log('CSIG: Generating PDF for element', i + 1);
                        
                        // Use a reasonable pixel ratio for PDF (not too high to avoid memory issues)
                        const pdfPixelRatio = Math.min(settings.pixelRatio, 3);
                        
                        let canvas;
                        try {
                            canvas = await htmlToImage.toCanvas(element, {
                                quality: 1,
                                pixelRatio: pdfPixelRatio,
                                useCORS: true,
                                allowTaint: true,
                                skipFonts: false,
                                cacheBust: true,
                                backgroundColor: '#ffffff',
                                fetchRequestInit: {
                                    cache: 'no-cache'
                                }
                            });
                        } catch (pdfError) {
                            console.warn('CSIG: PDF generation at high resolution failed, trying lower resolution:', pdfError);
                            
                            // Fallback to lower resolution for PDF
                            canvas = await htmlToImage.toCanvas(element, {
                                quality: 0.9,
                                pixelRatio: 2,
                                useCORS: true,
                                allowTaint: true,
                                skipFonts: false,
                                cacheBust: true,
                                backgroundColor: '#ffffff',
                                fetchRequestInit: {
                                    cache: 'no-cache'
                                }
                            });
                        }

                        const { jsPDF } = window.jspdf;
                        const pdf = new jsPDF({
                            orientation: canvas.width > canvas.height ? 'landscape' : 'portrait',
                            unit: 'px',
                            format: [canvas.width, canvas.height]
                        });

                        pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, canvas.width, canvas.height);
                        const base64data = pdf.output('datauristring');
                        
                        console.log('CSIG: PDF generated, sending to server...');

                        const formData = new FormData();
                        formData.append('action', 'csig_save_pdf');
                        formData.append('nonce', csigJobData.nonce);
                        formData.append('pdf_data', base64data);
                        formData.append('element_index', i);
                        formData.append('job_id', csigJobData.jobId);
                        formData.append('overwrite_files', settings.overwriteFiles ? '1' : '0');
                        
                        // Send custom filename if available
                        if (sanitizedFilename) {
                            formData.append('custom_filename', sanitizedFilename);
                        }

                        const pdfResponse = await fetch(csigJobData.ajaxUrl, {
                            method: 'POST',
                            body: formData
                        });

                        const pdfResult = await pdfResponse.json();
                        console.log('CSIG: PDF save result:', pdfResult);
                        
                        if (pdfResult.success) {
                            generatedFiles.push(pdfResult.data.url);
                            appendGeneratedFileToList(pdfResult.data.url, 'pdf');
                        } else {
                            console.error('CSIG: PDF save failed:', pdfResult);
                        }
                    } catch (error) {
                        console.error('CSIG: PDF generation failed:', error);
                    }
                }
                
                // Add delay between elements to prevent timing issues, especially with retina support
                if (i < elements.length - 1) { // Don't delay after the last element
                    const delayTime = isRetina ? 1000 : 500; // Longer delay for retina
                    console.log('CSIG: Waiting', delayTime, 'ms before next element...');
                    await new Promise(resolve => setTimeout(resolve, delayTime));
                }
            }

            // Show results
            console.log('CSIG: Generation complete, generated files:', generatedFiles);
            progressBar.style.width = '100%';
            statusText.textContent = `Successfully generated ${generatedFiles.length} file(s)!`;
            
            if (generatedFiles.length > 0) {
                fileList.innerHTML = generatedFiles.map(url => 
                    `<p style="margin: 5px 0;"><a href="${url}" target="_blank" class="button button-secondary" style="width: 100%; text-align: left; font-size: 11px; padding: 4px 8px;">📁 ${url.split('/').pop()}</a></p>`
                ).join('');
                resultsDiv.style.display = 'block';
                
                // Update job stats via AJAX
                const statsFormData = new FormData();
                statsFormData.append('action', 'csig_update_job_stats');
                statsFormData.append('nonce', csigJobData.nonce);
                statsFormData.append('job_id', csigJobData.jobId);
                statsFormData.append('generated_files', JSON.stringify(generatedFiles));
                
                fetch(csigJobData.ajaxUrl, {
                    method: 'POST',
                    body: statsFormData  // Fixed: was 'formData', now 'statsFormData'
                });
            }

            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 3000);

        } catch (error) {
            console.error('CSIG: Error generating files:', error);
            statusText.textContent = 'Error: ' + error.message;
            progressBar.style.width = '0%';
            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 5000);
        } finally {
            runButton.disabled = false;
            runButton.textContent = 'Generate Images Now';
        }
    });
});
