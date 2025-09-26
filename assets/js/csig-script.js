// At the top of the file, get the settings
const settings = csigData.settings;

document.addEventListener('DOMContentLoaded', function() {
    const loadButton = document.getElementById('csig-load');
    
    if (!loadButton) {
        console.log('CSIG: Load button not found on this page');
        return;
    }
    
    loadButton.addEventListener('click', async function() {
        const url = document.getElementById('csig-job-url')?.value || document.getElementById('csig-url')?.value;
        const format = document.getElementById('csig-job-format')?.value || document.querySelector('input[name="csig-format"]:checked')?.value;
        
        if (!url) return alert('Enter a URL first.');

        // Show status and disable button
        const statusDiv = document.getElementById('csig-status');
        const statusText = document.getElementById('csig-status-text');
        const progressBar = document.getElementById('csig-progress-bar');
        
        loadButton.disabled = true;
        loadButton.textContent = 'Processing...';
        statusDiv.style.display = 'block';
        statusText.textContent = 'Loading page in iframe...';
        progressBar.style.width = '10%';

        const iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.style.width = '1250px';
        iframe.style.height = '650px';
        iframe.style.border = '1px solid #ddd';
        document.getElementById('csig-preview').innerHTML = '';
        document.getElementById('csig-preview').appendChild(iframe);

        iframe.onload = async () => {
            try {
                progressBar.style.width = '20%';
                statusText.textContent = 'Iframe loaded, processing fonts...';
                
                // Inject CSS to hide #wpadminbar
                const styleElement = document.createElement('style');
                styleElement.textContent = '#wpadminbar { display: none !important; }';
                iframe.contentDocument.head.appendChild(styleElement);

                // Find ALL elements with the class - UPDATED DEFAULT
                const selector = settings.selector || '.csig-card';
                const bizCards = iframe.contentDocument.querySelectorAll(selector);
                if (bizCards.length === 0) {
                    resetUI();
                    alert(`No elements with selector "${selector}" found in the iframe.`);
                    return;
                }

                statusText.textContent = `Found ${bizCards.length} element(s). Loading fonts...`;
                progressBar.style.width = '30%';

                // Font handling (optimized)
                const fontLinks = iframe.contentDocument.querySelectorAll('link[href*="fonts.googleapis.com"]');
                
                if (fontLinks.length > 0) {
                    statusText.textContent = `Processing ${fontLinks.length} font(s)...`;
                    
                    const fontPromises = Array.from(fontLinks).map(async (link) => {
                        try {
                            const response = await fetch(link.href);
                            const cssText = await response.text();
                            const newStyleElement = document.createElement('style');
                            newStyleElement.textContent = cssText;
                            iframe.contentDocument.head.appendChild(newStyleElement);
                            return true;
                        } catch (err) {
                            console.warn('Failed to fetch font CSS:', err);
                            return false;
                        }
                    });
                    
                    await Promise.allSettled(fontPromises);
                    fontLinks.forEach(link => link.remove());
                    iframe.contentDocument.body.offsetHeight; // Force reflow
                }

                progressBar.style.width = '50%';
                statusText.textContent = 'Waiting for fonts to load...';

                // Wait for fonts with timeout
                const fontLoadPromises = [];
                if (iframe.contentDocument.fonts && iframe.contentDocument.fonts.ready) {
                    fontLoadPromises.push(iframe.contentDocument.fonts.ready);
                }

                // Add timeout to prevent hanging
                const fontTimeout = new Promise(resolve => setTimeout(resolve, 3000));
                await Promise.race([Promise.allSettled(fontLoadPromises), fontTimeout]);

                progressBar.style.width = '60%';
                statusText.textContent = 'Fonts loaded, preparing capture...';
                
                // Reduced wait time for better performance
                await new Promise(resolve => setTimeout(resolve, 1000));

                const results = document.createElement('div');
                results.style.marginTop = '20px';
                
                const header = document.createElement('h3');
                header.textContent = `Capturing ${bizCards.length} element(s) at ${settings.pixelRatio}x quality...`;
                results.appendChild(header);
                
                // Process each element
                for (let i = 0; i < bizCards.length; i++) {
                    const bizCard = bizCards[i];
                    const progress = 60 + (i / bizCards.length) * 35; // 60% to 95%
                    
                    progressBar.style.width = `${progress}%`;
                    statusText.textContent = `Capturing element ${i + 1} of ${bizCards.length}...`;
                    
                    // Add separator for multiple elements
                    if (i > 0) {
                        const separator = document.createElement('hr');
                        separator.style.margin = '20px 0';
                        results.appendChild(separator);
                    }
                    
                    const elementHeader = document.createElement('h4');
                    elementHeader.textContent = `Element ${i + 1}:`;
                    results.appendChild(elementHeader);
                    
                    // Optimized capture options
                    const commonOptions = {
                        width: bizCard.offsetWidth,
                        height: bizCard.offsetHeight,
                        skipAutoScale: true,
                        // Add performance optimizations
                        skipFonts: false,
                        cacheBust: false
                    };
                    
                    // Generate Raster (PNG)
                    if (format === 'raster' || format === 'both') {
                        try {
                            const pngData = await htmlToImage.toPng(bizCard, {
                                ...commonOptions,
                                backgroundColor: null,
                                pixelRatio: settings.pixelRatio
                            });

                            // Preview PNG (smaller for performance)
                            const img = document.createElement('img');
                            img.src = pngData;
                            img.style.maxWidth = '300px'; // Smaller preview
                            img.style.border = '1px solid red';
                            img.style.marginBottom = '10px';
                            img.style.display = 'block';
                            results.appendChild(img);

                            // Save PNG
                            const pngFormData = new FormData();
                            pngFormData.append('action', 'csig_save_image');
                            pngFormData.append('nonce', csigData.nonce);
                            pngFormData.append('image_data', pngData);
                            pngFormData.append('element_index', i);

                            const pngRes = await fetch(csigData.ajaxUrl, {
                                method: 'POST',
                                body: pngFormData
                            });

                            const pngJson = await pngRes.json();
                            if (pngJson.success) {
                                const pngLink = document.createElement('p');
                                pngLink.innerHTML = `PNG ${i + 1} Saved: <a href="${pngJson.data.url}" target="_blank">${pngJson.data.url}</a>`;
                                results.appendChild(pngLink);
                            }
                        } catch (error) {
                            console.error(`Error generating PNG for element ${i + 1}:`, error);
                            const errorMsg = document.createElement('p');
                            errorMsg.textContent = `Error generating PNG for element ${i + 1}: ${error.message}`;
                            errorMsg.style.color = 'red';
                            results.appendChild(errorMsg);
                        }
                    }

                    // Generate Vector (PDF) - use slightly higher quality for PDFs
                    if (format === 'vector' || format === 'both') {
                        try {
                            const pdfPixelRatio = Math.max(2, settings.pixelRatio); // Minimum 2x for PDF
                            const pdfPngData = await htmlToImage.toPng(bizCard, {
                                ...commonOptions,
                                backgroundColor: 'white',
                                pixelRatio: pdfPixelRatio
                            });

                            const pixelsToPoints = 0.75;
                            const widthPt = bizCard.offsetWidth * pixelsToPoints;
                            const heightPt = bizCard.offsetHeight * pixelsToPoints;

                            const { jsPDF } = window.jspdf;
                            const pdf = new jsPDF({
                                orientation: bizCard.offsetWidth > bizCard.offsetHeight ? 'landscape' : 'portrait',
                                unit: 'pt',
                                format: [widthPt, heightPt]
                            });

                            pdf.addImage(pdfPngData, 'PNG', 0, 0, widthPt, heightPt);
                            const pdfBlob = pdf.output('blob');
                            
                            const reader = new FileReader();
                            reader.onloadend = async function() {
                                const base64data = reader.result;
                                
                                const pdfFormData = new FormData();
                                pdfFormData.append('action', 'csig_save_pdf');
                                pdfFormData.append('nonce', csigData.nonce);
                                pdfFormData.append('pdf_data', base64data);
                                pdfFormData.append('element_index', i);

                                const pdfRes = await fetch(csigData.ajaxUrl, {
                                    method: 'POST',
                                    body: pdfFormData
                                });

                                const pdfJson = await pdfRes.json();
                                if (pdfJson.success) {
                                    const pdfLink = document.createElement('p');
                                    pdfLink.innerHTML = `PDF ${i + 1} Saved: <a href="${pdfJson.data.url}" target="_blank">${pdfJson.data.url}</a>`;
                                    results.appendChild(pdfLink);
                                }
                            };
                            reader.readAsDataURL(pdfBlob);
                        } catch (error) {
                            console.error(`Error generating PDF for element ${i + 1}:`, error);
                            const errorMsg = document.createElement('p');
                            errorMsg.textContent = `Error generating PDF for element ${i + 1}: ${error.message}`;
                            errorMsg.style.color = 'red';
                            results.appendChild(errorMsg);
                        }
                    }
                    
                    // Smaller delay between captures for better performance
                    if (i < bizCards.length - 1) {
                        await new Promise(resolve => setTimeout(resolve, 100));
                    }
                }

                // Completion
                progressBar.style.width = '100%';
                statusText.textContent = 'Complete!';
                
                const completion = document.createElement('p');
                completion.innerHTML = `<strong>✅ Completed capturing ${bizCards.length} element(s)!</strong>`;
                completion.style.color = 'green';
                completion.style.marginTop = '20px';
                results.appendChild(completion);

                document.getElementById('csig-preview').appendChild(results);
                
                // Hide status after completion
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 3000);

            } catch (error) {
                console.error('Error generating files:', error);
                alert('Failed to generate files: ' + error.message);
            } finally {
                resetUI();
            }
        };

        iframe.onerror = function(error) {
            console.error('Iframe failed to load:', error);
            resetUI();
            alert('Failed to load iframe');
        };
        
        function resetUI() {
            loadButton.disabled = false;
            loadButton.textContent = 'Load and Capture';
            statusDiv.style.display = 'none';
        }
    });
});