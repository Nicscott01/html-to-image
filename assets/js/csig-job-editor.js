document.addEventListener('DOMContentLoaded', function() {
    const runButton = document.getElementById('csig-run-job');
    
    if (!runButton) {
        console.log('CSIG: Run button not found');
        return;
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
        const format = settings.outputFormat;
        
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

            // Wait for images to load with timeout
            await new Promise(resolve => {
                const images = iframe.contentDocument.querySelectorAll('img');
                let loadedCount = 0;
                const totalImages = images.length;
                let timeoutId;
                
                console.log('CSIG: Found', totalImages, 'images to load');

                const finish = () => {
                    if (timeoutId) {
                        clearTimeout(timeoutId);
                    }
                    console.log('CSIG: Image loading complete (loaded:', loadedCount, '/', totalImages, ')');
                    setTimeout(resolve, 1000); // Wait 1 second for final rendering
                };

                if (totalImages === 0) {
                    console.log('CSIG: No images found, waiting 1 second for final rendering');
                    setTimeout(resolve, 1000);
                    return;
                }

                timeoutId = setTimeout(() => {
                    console.log('CSIG: Image loading timeout reached, proceeding anyway');
                    finish();
                }, 8000);

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
                        }, 4000);
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

                // Generate PNG if needed
                if (format === 'raster' || format === 'both') {
                    try {
                        console.log('CSIG: Generating PNG for element', i + 1);
                        
                        const pngData = await htmlToImage.toPng(element, {
                            quality: 1,
                            pixelRatio: settings.pixelRatio,
                            useCORS: true,
                            allowTaint: true,
                            skipFonts: false,
                            cacheBust: true
                        });
                        
                        console.log('CSIG: PNG generated, sending to server...');

                        const formData = new FormData();
                        formData.append('action', 'csig_save_image');
                        formData.append('nonce', csigJobData.nonce);
                        formData.append('image_data', pngData);
                        formData.append('element_index', i);
                        formData.append('job_id', csigJobData.jobId);

                        const pngResponse = await fetch(csigJobData.ajaxUrl, {
                            method: 'POST',
                            body: formData
                        });

                        const pngResult = await pngResponse.json();
                        console.log('CSIG: PNG save result:', pngResult);
                        
                        if (pngResult.success) {
                            generatedFiles.push(pngResult.data.url);
                        } else {
                            console.error('CSIG: PNG save failed:', pngResult);
                        }
                    } catch (error) {
                        console.error('CSIG: PNG generation failed:', error);
                    }
                }

                // Generate PDF if needed
                if (format === 'vector' || format === 'both') {
                    try {
                        console.log('CSIG: Generating PDF for element', i + 1);
                        
                        const canvas = await htmlToImage.toCanvas(element, {
                            quality: 1,
                            pixelRatio: 2,
                            useCORS: true,
                            allowTaint: true,
                            skipFonts: false,
                            cacheBust: true
                        });

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

                        const pdfResponse = await fetch(csigJobData.ajaxUrl, {
                            method: 'POST',
                            body: formData
                        });

                        const pdfResult = await pdfResponse.json();
                        console.log('CSIG: PDF save result:', pdfResult);
                        
                        if (pdfResult.success) {
                            generatedFiles.push(pdfResult.data.url);
                        } else {
                            console.error('CSIG: PDF save failed:', pdfResult);
                        }
                    } catch (error) {
                        console.error('CSIG: PDF generation failed:', error);
                    }
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
                    body: statsFormData
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