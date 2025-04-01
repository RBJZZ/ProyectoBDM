
        let mouseX = 0;
        let mouseY = 0;
        const sensitivity = 0.02; 

        function createStars() {
            const container = document.getElementById('stars');
            for(let i = 0; i < 150; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.width = Math.random() * 3 + 'px';
                star.style.height = star.style.width;
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.animationDelay = Math.random() * 1.5 + 's';
                container.appendChild(star);
            }
        }

        function updateStarsPosition() {
            const starsContainer = document.getElementById('stars');
            const centerX = window.innerWidth / 2;
            const centerY = window.innerHeight / 2;
            
            const offsetX = (mouseX - centerX) * -sensitivity;
            const offsetY = (mouseY - centerY) * -sensitivity;
            
            starsContainer.style.transform = `translate(
                calc(-50% + ${offsetX}px),
                calc(-50% + ${offsetY}px)
            )`;
            
            requestAnimationFrame(updateStarsPosition);
        }

        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
        });

        function createShootingStar() {
            const star = document.createElement('div');
            star.className = 'shooting-star';

            const startX = Math.random() * 120 - 10; 
            const startY = -10; 
            
            
            const angle = -45; 
            const velocityVariation = 0.8 + Math.random() * 0.4; 

            star.style.left = `${startX}%`;
            star.style.top = `${startY}%`;
            star.style.transform = `rotate(${angle}deg)`;
            
            star.style.width = `${150 + Math.random() * 100}px`;
            star.style.opacity = 0.5 + Math.random() * 0.3;
            star.style.animationDuration = `${velocityVariation}s`;

            document.body.appendChild(star);

            setTimeout(() => star.remove(), velocityVariation * 1000);
        }

        function scheduleShootingStar() {
            setTimeout(() => {
                createShootingStar();
                scheduleShootingStar();
            }, 4000 + Math.random() * 8000);
        }

        function populateDates() {
         
            const daySelect = document.getElementById('birth-day');
            for(let d = 1; d <= 31; d++) {
                const option = document.createElement('option');
                option.value = d;
                option.textContent = d;
                daySelect.appendChild(option);
            }

            
            const yearSelect = document.getElementById('birth-year');
            const currentYear = new Date().getFullYear();
            for(let y = currentYear; y >= 1900; y--) {
                const option = document.createElement('option');
                option.value = y;
                option.textContent = y;
                yearSelect.appendChild(option);
            }
        }

        
        populateDates();
        createStars();
        updateStarsPosition();
        scheduleShootingStar();
