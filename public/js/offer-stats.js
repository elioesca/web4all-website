document.addEventListener('DOMContentLoaded', () => {
    const track = document.getElementById('statsTrack');
    const prevBtn = document.getElementById('statsPrev');
    const nextBtn = document.getElementById('statsNext');
    const dots = document.querySelectorAll('.offer-stats-dot');

    if (!track || !prevBtn || !nextBtn || !dots.length) {
        return;
    }

    let currentIndex = 0;
    const totalSlides = dots.length;

    function updateCarousel() {
        track.style.transform = `translateX(-${currentIndex * 100}%)`;

        dots.forEach((dot, index) => {
            dot.classList.toggle('is-active', index === currentIndex);
        });
    }

    prevBtn.addEventListener('click', () => {
        currentIndex = currentIndex === 0 ? totalSlides - 1 : currentIndex - 1;
        updateCarousel();
    });

    nextBtn.addEventListener('click', () => {
        currentIndex = currentIndex === totalSlides - 1 ? 0 : currentIndex + 1;
        updateCarousel();
    });

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            currentIndex = parseInt(dot.dataset.slide, 10);
            updateCarousel();
        });
    });

    updateCarousel();
});