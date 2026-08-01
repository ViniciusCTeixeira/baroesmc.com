(function () {
    'use strict';

    const rail = document.getElementById('collaborator-float-rail');
    const collaborators = document.getElementById('colaboradores');
    const carousel = document.querySelector('.collaborator-float-carousel');
    const track = document.querySelector('.collaborator-float-track');
    const progress = document.getElementById('collaborator-float-progress');
    const originalSlides = track ? Array.from(track.querySelectorAll('.collaborator-float-item')) : [];
    if (!rail || !collaborators || !carousel || !track || !originalSlides.length) return;

    const originalCount = originalSlides.length;
    const visibleCount = Math.min(3, originalCount);
    let trackSlides = originalSlides.slice();
    let displayIndex = 0;
    let carouselTimer = null;
    let slideSize = 0;
    let slideGap = 10;

    // Clones only serve to complete the last rolling transition. They are
    // never used when there are fewer than three different logos.
    if (originalCount >= 3) {
        originalSlides.slice(0, visibleCount).forEach(function (slide) {
            const clone = slide.cloneNode(true);
            clone.dataset.carouselClone = 'true';
            track.appendChild(clone);
        });
        trackSlides = Array.from(track.querySelectorAll('.collaborator-float-item'));
    }

    function isMobileCarousel() {
        return window.matchMedia('(max-width: 680px)').matches;
    }

    function syncVisibility() {
        rail.classList.toggle('is-visible', !collaboratorsVisible);
    }

    function updateGeometry() {
        const mobile = isMobileCarousel();
        slideGap = mobile ? 8 : 10;
        const carouselStyles = window.getComputedStyle(carousel);
        const horizontalPadding = parseFloat(carouselStyles.paddingLeft) + parseFloat(carouselStyles.paddingRight);
        const verticalPadding = parseFloat(carouselStyles.paddingTop) + parseFloat(carouselStyles.paddingBottom);
        const available = mobile
            ? carousel.clientWidth - horizontalPadding
            : carousel.clientHeight - verticalPadding;
        slideSize = Math.max(1, (available - (slideGap * (visibleCount - 1))) / visibleCount);
        track.style.gap = slideGap + 'px';
        track.style.width = mobile ? (slideSize * trackSlides.length + slideGap * (trackSlides.length - 1)) + 'px' : '100%';
        track.style.height = mobile ? '100%' : (slideSize * trackSlides.length + slideGap * (trackSlides.length - 1)) + 'px';
        trackSlides.forEach(function (slide) {
            slide.style.flexBasis = slideSize + 'px';
            slide.style.height = mobile ? '100%' : slideSize + 'px';
            slide.style.width = mobile ? slideSize + 'px' : '100%';
        });
    }

    function render(index, animate) {
        displayIndex = index;
        const distance = displayIndex * (slideSize + slideGap);
        track.style.transition = animate === false ? 'none' : '';
        track.style.transform = isMobileCarousel()
            ? 'translate3d(-' + distance + 'px, 0, 0)'
            : 'translate3d(0, -' + distance + 'px, 0)';

        trackSlides.forEach(function (slide, slideIndex) {
            const visible = slideIndex >= displayIndex && slideIndex < displayIndex + visibleCount;
            const isClone = slide.dataset.carouselClone === 'true';
            slide.setAttribute('aria-hidden', visible ? 'false' : 'true');
            slide.tabIndex = visible && !isClone ? 0 : -1;
        });

        if (progress) {
            progress.textContent = ((displayIndex % originalCount) + 1) + ' / ' + originalCount;
        }
    }

    function nextSlide() {
        if (originalCount < 3) return;
        const nextIndex = displayIndex + 1;
        render(nextIndex, true);
        if (nextIndex === originalCount) {
            window.setTimeout(function () {
                render(0, false);
                window.requestAnimationFrame(function () {
                    track.style.transition = '';
                });
            }, 1300);
        }
    }

    function startCarousel() {
        if (originalCount < 3 || carouselTimer) return;
        carouselTimer = window.setInterval(nextSlide, 4500);
    }

    function stopCarousel() {
        if (!carouselTimer) return;
        window.clearInterval(carouselTimer);
        carouselTimer = null;
    }

    let collaboratorsVisible = false;
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.target === collaborators) collaboratorsVisible = entry.isIntersecting;
            });
            syncVisibility();
        }, {rootMargin: '-8% 0px -8% 0px', threshold: 0.08});
        observer.observe(collaborators);
    } else {
        function fallbackVisibility() {
            const collaboratorsRect = collaborators.getBoundingClientRect();
            collaboratorsVisible = collaboratorsRect.bottom > 0 && collaboratorsRect.top < window.innerHeight;
            syncVisibility();
        }
        window.addEventListener('scroll', fallbackVisibility, {passive: true});
        window.addEventListener('resize', fallbackVisibility);
        fallbackVisibility();
    }

    carousel.addEventListener('mouseenter', stopCarousel);
    carousel.addEventListener('mouseleave', startCarousel);
    carousel.addEventListener('focusin', stopCarousel);
    carousel.addEventListener('focusout', startCarousel);
    function refreshLayout() {
        updateGeometry();
        render(displayIndex, false);
        window.requestAnimationFrame(function () {
            updateGeometry();
            render(displayIndex, false);
        });
    }

    window.addEventListener('resize', refreshLayout);
    const breakpoint = window.matchMedia('(max-width: 680px)');
    if (breakpoint.addEventListener) breakpoint.addEventListener('change', refreshLayout);

    updateGeometry();
    render(0, false);
    syncVisibility();
    startCarousel();
}());
