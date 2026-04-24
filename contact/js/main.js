// Main JavaScript file for shared functionality across public pages

document.addEventListener('DOMContentLoaded', () => {
    // --- Load Header and Footer ---
    const loadComponent = async (url, elementId) => {
        try {
            const response = await fetch(url);
            if (!response.ok) return;
            const text = await response.text();
            const element = document.getElementById(elementId);
            if (element) element.innerHTML = text;
        } catch (error) {
            console.error(`Failed to load component from ${url}:`, error);
        }
    };

    const loadHeaderAndFooter = async () => {
        // Use the new header.html and footer.html files
        await loadComponent('header.html', 'header-placeholder');
        await loadComponent('footer.html', 'footer-placeholder');
        
        // After components are loaded, initialize scripts and load dynamic data
        initializeSharedScripts();
        loadSettings(); 
    };

    // --- Load Dynamic Site-Wide Settings from API ---
    const loadSettings = async () => {
        try {
            const response = await fetch('api/api.php?collection=settings');
            if (!response.ok) throw new Error('Failed to fetch settings');
            const settings = await response.json();

            // Populate Footer Contact Info
            const contactInfo = document.getElementById('footer-contact-info');
            if (contactInfo) {
                contactInfo.innerHTML = `
                    <p class="flex items-start"><i class="fa-solid fa-location-dot w-4 mr-3 mt-1 text-[var(--primary-red)]"></i><span>${settings.address || ''}</span></p>
                    <p class="flex items-start"><i class="fa-solid fa-envelope w-4 mr-3 mt-1 text-[var(--primary-red)]"></i><a href="mailto:${settings.email || '#'}" class="hover:text-[var(--primary-red)]">${settings.email || ''}</a></p>
                    <p class="flex items-start"><i class="fa-solid fa-phone w-4 mr-3 mt-1 text-[var(--primary-red)]"></i><a href="tel:${settings.phone || '#'}" class="hover:text-[var(--primary-red)]">${settings.phone || ''}</a></p>
                `;
            }
            
            // Populate Footer Social Media Links
            const socialLinks = document.getElementById('footer-social-links');
            if (socialLinks) {
                socialLinks.innerHTML = `
                    ${settings.facebook_url ? `<a href="${settings.facebook_url}" aria-label="Facebook" class="text-gray-500 hover:text-[var(--primary-red)] transition-colors"><i class="fa-brands fa-facebook-f fa-lg"></i></a>` : ''}
                    ${settings.instagram_url ? `<a href="${settings.instagram_url}" aria-label="Instagram" class="text-gray-500 hover:text-[var(--primary-red)] transition-colors"><i class="fa-brands fa-instagram fa-lg"></i></a>` : ''}
                    ${settings.twitter_url ? `<a href="${settings.twitter_url}" aria-label="Twitter" class="text-gray-500 hover:text-[var(--primary-red)] transition-colors"><i class="fa-brands fa-x-twitter fa-lg"></i></a>` : ''}
                    ${settings.linkedin_url ? `<a href="${settings.linkedin_url}" aria-label="LinkedIn" class="text-gray-500 hover:text-[var(--primary-red)] transition-colors"><i class="fa-brands fa-linkedin-in fa-lg"></i></a>` : ''}
                `;
            }

            // Populate other footer details
            if(document.getElementById('footer-rera')) document.getElementById('footer-rera').textContent = `RERA: ${settings.rera_number || ''}`;
            if(document.getElementById('footer-copyright')) document.getElementById('footer-copyright').textContent = settings.copyright_text || `© ${new Date().getFullYear()} Right Value Property. All Rights Reserved.`;

        } catch (error) {
            console.error('Failed to load site settings:', error);
        }
    };

    // --- SHARED SCRIPTS INITIALIZER ---
    const initializeSharedScripts = () => {
        // Active Nav Link Logic
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        const navLinks = document.querySelectorAll('header .nav-link, header #mobile-menu a');
        navLinks.forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active', 'text-[var(--primary-red)]', 'font-semibold');
            }
        });

        // Mobile Menu Toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }

        // Header Scroll Effect
        const header = document.querySelector('header');
        if (header) {
            window.addEventListener('scroll', () => {
                header.classList.toggle('scrolled', window.scrollY > 50);
            });
        }

        // Dynamic Office Hours Logic
        const statusElement = document.getElementById('office-status');
        const timeElement = document.getElementById('current-time');
        if (statusElement && timeElement) {
            const updateOfficeStatus = () => {
                const now = new Date();
                const options = { timeZone: 'Asia/Kolkata', hour: '2-digit', minute: '2-digit', hour12: true };
                const timeString = now.toLocaleTimeString('en-IN', options);
                const day = now.getDay(); // 0 is Sunday
                const hour = parseInt(now.toLocaleString('en-US', { timeZone: 'Asia/Kolkata', hour: '2-digit', hour12: false }));
                const isOpen = (day !== 0 && hour >= 10 && hour < 19);
                
                timeElement.textContent = `${timeString} (IST)`;
                statusElement.textContent = isOpen ? 'Open Now' : 'Closed';
                statusElement.classList.toggle('open', isOpen);
                statusElement.classList.toggle('closed', !isOpen);
            };
            updateOfficeStatus();
            setInterval(updateOfficeStatus, 60000); // Update every minute
        }
    };

    // Start the process
    loadHeaderAndFooter();
});
