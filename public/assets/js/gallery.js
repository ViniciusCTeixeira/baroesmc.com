(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }
        callback();
    }

    ready(function () {
        var modal = document.getElementById('site-gallery-modal');
        var image = document.getElementById('site-gallery-modal-image');
        var description = document.getElementById('site-gallery-modal-description');
        var counter = document.getElementById('site-gallery-modal-counter');
        var previous = document.getElementById('site-gallery-prev');
        var next = document.getElementById('site-gallery-next');
        var items = Array.prototype.slice.call(document.querySelectorAll('.gallery-item'));
        var currentIndex = 0;

        if (!modal || !image || !description || !counter || !previous || !next || !items.length) {
            return;
        }

        function render(index) {
            currentIndex = (index + items.length) % items.length;
            var item = items[currentIndex];
            var source = item.getAttribute('data-gallery-image') || '';
            var text = item.getAttribute('data-gallery-description') || 'Imagem da galeria';

            image.src = source;
            image.alt = text;
            description.textContent = text;
            counter.textContent = 'Imagem ' + (currentIndex + 1) + ' de ' + items.length;
            previous.hidden = items.length < 2;
            next.hidden = items.length < 2;
        }

        items.forEach(function (item, index) {
            item.addEventListener('click', function () {
                render(index);
            });
        });

        previous.addEventListener('click', function () {
            render(currentIndex - 1);
        });

        next.addEventListener('click', function () {
            render(currentIndex + 1);
        });

        modal.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                render(currentIndex - 1);
            }
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                render(currentIndex + 1);
            }
        });

        modal.addEventListener('hidden.bs.modal', function () {
            image.removeAttribute('src');
            image.alt = '';
            description.textContent = '';
        });
    });
})();
