<?php
/**
 * Plugin Name: Client-Side Image Generator
 * Description: Proof-of-concept for generating PNGs in the browser and saving to WP uploads.
 * Version: 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function () {
    add_menu_page(
        'Client-Side Image Generator',
        'Image Generator',
        'manage_options',
        'client-side-image-generator',
        'csig_admin_page'
    );
});

function csig_admin_page() {
    ?>
    <div class="wrap">
        <h1>Client-Side Image Generator</h1>
        <p>Enter a URL, then click "Load and Capture".</p>
        <input type="url" id="csig-url" style="width: 400px;" placeholder="https://example.com" />
        <button class="button button-primary" id="csig-load">Load and Capture</button>
        <div id="csig-preview" style="margin-top:20px;"></div>
    </div>
    <?php
    wp_enqueue_script( 'html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', [], null, true );
    wp_add_inline_script( 'html2canvas', csig_js() );
    wp_localize_script( 'html2canvas', 'csigData', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'csig_save_image' ),
    ] );
}

// AJAX handler to save PNG
add_action( 'wp_ajax_csig_save_image', function () {
    check_ajax_referer( 'csig_save_image', 'nonce' );

    $image_data = isset($_POST['image_data']) ? $_POST['image_data'] : '';
    if ( ! $image_data ) wp_send_json_error( 'Missing image data' );

    $upload_dir = wp_upload_dir();
    $filename   = 'csig-' . time() . '.png';
    $file_path  = $upload_dir['basedir'] . '/' . $filename;

    // Strip base64 header and decode
    $image_parts = explode( ',', $image_data );
    $image_base64 = base64_decode( end( $image_parts ) );
    file_put_contents( $file_path, $image_base64 );

    wp_send_json_success( [ 'url' => $upload_dir['baseurl'] . '/' . $filename ] );
});

function csig_js() {
    return <<<JS
document.getElementById('csig-load').addEventListener('click', async function() {
    const url = document.getElementById('csig-url').value;
    if (!url) return alert('Enter a URL first.');

    const iframe = document.createElement('iframe');
    iframe.src = url;
    iframe.style.width = '1440px';
    iframe.style.height = '800px';
    iframe.style.border = '1px solid #ddd';
    document.getElementById('csig-preview').innerHTML = '';
    document.getElementById('csig-preview').appendChild(iframe);

    iframe.onload = async () => {
        // Inject CSS to hide #wpadminbar
        const styleElement = document.createElement('style');
        styleElement.textContent = '#wpadminbar { display: none !important; }';
        iframe.contentDocument.head.appendChild(styleElement);

        const bizCard = iframe.contentDocument.querySelector('.my-biz-card');
        if (!bizCard) {
            alert('Element with class .my-biz-card not found in the iframe.');
            return;
        }

        // Wait for layout to settle
        await new Promise(resolve => setTimeout(resolve, 300));

        // Capture ONLY the specific element, not the whole page
        const scale = 2;
        const elementCanvas = await html2canvas(bizCard, {
            backgroundColor: null,
            scale: scale,
            useCORS: true,
            allowTaint: true,
            windowWidth: 1440,
            windowHeight: 800
        });

        const pngData = elementCanvas.toDataURL('image/png');

        // Preview
        const img = document.createElement('img');
        img.src = pngData;
        img.style.maxWidth = '100%';
        img.style.border = '1px solid red'; // Debug border
        document.getElementById('csig-preview').appendChild(img);

        // Save via AJAX
        const formData = new FormData();
        formData.append('action', 'csig_save_image');
        formData.append('nonce', csigData.nonce);
        formData.append('image_data', pngData);

        const res = await fetch(csigData.ajaxUrl, {
            method: 'POST',
            body: formData
        });

        const json = await res.json();
        if (json.success) {
            const link = document.createElement('p');
            link.innerHTML = 'Saved: <a href="' + json.data.url + '" target="_blank">' + json.data.url + '</a>';
            document.getElementById('csig-preview').appendChild(link);
        } else {
            alert('Failed to save image: ' + json.data);
        }
    };
});
JS;
}