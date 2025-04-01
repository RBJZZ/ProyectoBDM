
const reelsWrapper = document.getElementById('reelsWrapper');
const reels = Array.from(document.querySelectorAll('.video-reel'));
let currentIndex = 0;

function navigate(direction) {
    const newIndex = currentIndex + direction;
    if (newIndex >= 0 && newIndex < reels.length) {
        reels[currentIndex].classList.remove('active');
        currentIndex = newIndex;
        reels[currentIndex].classList.add('active');
        scrollToActiveReel();
    }
}

function scrollToActiveReel() {
    const activeReel = reels[currentIndex];
    activeReel.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });
}

let isScrolling = false;
reelsWrapper.addEventListener('scroll', () => {
    if (!isScrolling) {
        isScrolling = true;
        setTimeout(() => {
            const wrapperRect = reelsWrapper.getBoundingClientRect();
            const centerPosition = wrapperRect.top + (wrapperRect.height / 2);
            
            reels.forEach((reel, index) => {
                const reelRect = reel.getBoundingClientRect();
                if (reelRect.top <= centerPosition && reelRect.bottom >= centerPosition) {
                    currentIndex = index;
                }
            });
            isScrolling = false;
        }, 100);
    }
});
