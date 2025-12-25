/**
 * Homepage functionality
 */

(function() {
    'use strict';

    // Hero Slider
    class HeroSlider {
        constructor(container) {
            this.container = container;
            this.slides = container.querySelectorAll('.hero-slide');
            this.dots = container.querySelectorAll('.hero-dot');
            this.prevBtn = container.querySelector('.hero-prev');
            this.nextBtn = container.querySelector('.hero-next');
            this.currentSlide = 0;
            this.autoplayInterval = null;
            this.autoplayDelay = 5000; // 5 seconds

            this.init();
        }

        init() {
            if (this.slides.length <= 1) return;

            // Set up event listeners
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', () => this.prevSlide());
            }

            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', () => this.nextSlide());
            }

            // Set up dot navigation
            this.dots.forEach((dot, index) => {
                dot.addEventListener('click', () => this.goToSlide(index));
            });

            // Start autoplay
            this.startAutoplay();

            // Pause autoplay on hover
            this.container.addEventListener('mouseenter', () => this.stopAutoplay());
            this.container.addEventListener('mouseleave', () => this.startAutoplay());

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') this.prevSlide();
                if (e.key === 'ArrowRight') this.nextSlide();
            });
        }

        goToSlide(index) {
            // Remove active class from current slide and dot
            this.slides[this.currentSlide].classList.remove('active');
            this.dots[this.currentSlide]?.classList.remove('active');

            // Set new current slide
            this.currentSlide = index % this.slides.length;
            if (this.currentSlide < 0) {
                this.currentSlide = this.slides.length - 1;
            }

            // Add active class to new slide and dot
            this.slides[this.currentSlide].classList.add('active');
            this.dots[this.currentSlide]?.classList.add('active');

            // Reset autoplay
            this.resetAutoplay();
        }

        nextSlide() {
            this.goToSlide(this.currentSlide + 1);
        }

        prevSlide() {
            this.goToSlide(this.currentSlide - 1);
        }

        startAutoplay() {
            this.autoplayInterval = setInterval(() => {
                this.nextSlide();
            }, this.autoplayDelay);
        }

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        }

        resetAutoplay() {
            this.stopAutoplay();
            this.startAutoplay();
        }
    }

    // Lazy Loading
    class LazyLoader {
        constructor() {
            this.images = document.querySelectorAll('img[loading="lazy"]');
            this.imageObserver = null;

            this.init();
        }

        init() {
            if ('IntersectionObserver' in window) {
                this.imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            this.loadImage(img);
                            this.imageObserver.unobserve(img);
                        }
                    });
                }, {
                    rootMargin: '50px'
                });

                this.images.forEach(img => {
                    this.imageObserver.observe(img);
                });
            } else {
                // Fallback for browsers without IntersectionObserver
                this.images.forEach(img => {
                    this.loadImage(img);
                });
            }
        }

        loadImage(img) {
            if (img.dataset.src) {
                img.src = img.dataset.src;
                delete img.dataset.src;
            }
            
            img.addEventListener('load', () => {
                img.classList.add('loaded');
                // Remove loading placeholder from parent
                const card = img.closest('.collection-card, .promotional-banner');
                if (card) {
                    card.classList.add('loaded');
                }
            });

            img.addEventListener('error', () => {
                // Handle error - show placeholder
                img.src = '/images/placeholder.jpg';
            });
        }
    }

    // Smooth Scroll
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#') return;

                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize hero slider
        const heroSection = document.querySelector('.hero-section');
        if (heroSection) {
            new HeroSlider(heroSection);
        }

        // Initialize lazy loading
        new LazyLoader();

        // Initialize smooth scroll
        initSmoothScroll();

        // Add fade-in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const fadeInObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-visible');
                    fadeInObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe sections for fade-in
        document.querySelectorAll('.featured-collections, .bestsellers, .new-arrivals').forEach(section => {
            section.classList.add('fade-in-section');
            fadeInObserver.observe(section);
        });
    });

    // Add fade-in styles
    const style = document.createElement('style');
    style.textContent = `
        .fade-in-section {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        .fade-in-section.fade-in-visible {
            opacity: 1;
            transform: translateY(0);
        }
    `;
    document.head.appendChild(style);
})();

