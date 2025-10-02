(function (window) {
    'use strict';

    if (window && window.htmlToImage && typeof window.htmlToImage.toPng === 'function') {
        return;
    }

    function isElement(node) {
        return node && node.nodeType === Node.ELEMENT_NODE;
    }

    function cloneNode(node) {
        return node.cloneNode(true);
    }

    function inlineStyles(sourceNode, targetNode) {
        if (!isElement(sourceNode) || !isElement(targetNode)) {
            return;
        }

        var sourceStyle = window.getComputedStyle(sourceNode);

        if (targetNode.style) {
            for (var i = 0; i < sourceStyle.length; i++) {
                var property = sourceStyle[i];
                targetNode.style.setProperty(
                    property,
                    sourceStyle.getPropertyValue(property),
                    sourceStyle.getPropertyPriority(property)
                );
            }
        }

        var sourceChildren = sourceNode.children || [];
        var targetChildren = targetNode.children || [];
        for (var j = 0; j < sourceChildren.length; j++) {
            inlineStyles(sourceChildren[j], targetChildren[j]);
        }
    }

    function getDimensions(node, options) {
        var rect = node.getBoundingClientRect();
        var width = Math.ceil(options.width || rect.width || node.offsetWidth || node.scrollWidth || 1);
        var height = Math.ceil(options.height || rect.height || node.offsetHeight || node.scrollHeight || 1);
        return { width: width, height: height };
    }

    function createSvg(node, options) {
        var clone = cloneNode(node);
        inlineStyles(node, clone);

        var dims = getDimensions(node, options);
        var width = dims.width;
        var height = dims.height;

        var svgNS = 'http://www.w3.org/2000/svg';
        var xhtmlNS = 'http://www.w3.org/1999/xhtml';

        var svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('xmlns', svgNS);
        svg.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        svg.setAttribute('width', width);
        svg.setAttribute('height', height);
        svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);

        var foreignObject = document.createElementNS(svgNS, 'foreignObject');
        foreignObject.setAttribute('x', '0');
        foreignObject.setAttribute('y', '0');
        foreignObject.setAttribute('width', '100%');
        foreignObject.setAttribute('height', '100%');

        var container = document.createElement('div');
        container.setAttribute('xmlns', xhtmlNS);
        container.style.margin = '0';
        container.style.padding = '0';
        container.style.width = width + 'px';
        container.style.height = height + 'px';
        container.appendChild(clone);

        foreignObject.appendChild(container);
        svg.appendChild(foreignObject);

        var xml = new XMLSerializer().serializeToString(svg);
        var encoded = encodeURIComponent(xml);
        var dataUrl = 'data:image/svg+xml;charset=utf-8,' + encoded;

        return {
            dataUrl: dataUrl,
            width: width,
            height: height
        };
    }

    function toPng(node, options) {
        options = options || {};

        if (!node) {
            return Promise.reject(new Error('No element provided.'));
        }

        var svgData = createSvg(node, options);
        var pixelRatio = Math.max(options.pixelRatio || 1, 1);
        var backgroundColor = options.backgroundColor || null;

        return new Promise(function (resolve, reject) {
            var img = new Image();
            if (options.useCORS) {
                img.crossOrigin = 'anonymous';
            }

            img.onload = function () {
                try {
                    var canvas = document.createElement('canvas');
                    canvas.width = svgData.width * pixelRatio;
                    canvas.height = svgData.height * pixelRatio;

                    var ctx = canvas.getContext('2d');

                    if (backgroundColor) {
                        ctx.fillStyle = backgroundColor;
                        ctx.fillRect(0, 0, canvas.width, canvas.height);
                    } else {
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                    }

                    ctx.scale(pixelRatio, pixelRatio);
                    ctx.drawImage(img, 0, 0);

                    var quality = typeof options.quality === 'number' ? options.quality : 1;
                    var dataUrl = canvas.toDataURL('image/png', quality);
                    resolve(dataUrl);
                } catch (error) {
                    reject(error);
                }
            };

            img.onerror = function (error) {
                reject(error instanceof Error ? error : new Error('Failed to render node.'));
            };

            if (options.cacheBust) {
                svgData.dataUrl += (svgData.dataUrl.indexOf('?') === -1 ? '?' : '&') + 'csigcb=' + Date.now();
            }

            img.src = svgData.dataUrl;
        });
    }

    window.htmlToImage = {
        toPng: toPng
    };

}(typeof window !== 'undefined' ? window : this));
