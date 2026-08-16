/**
 * Language Switcher Component
 */

class LanguageSwitcher {
    constructor() {
        this.currentLang = this.getCurrentLanguage();
        this.init();
    }

    init() {
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.language-switcher')) {
                this.closeDropdown();
            }
        });
    }

    getCurrentLanguage() {
        // Get from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('lang')) {
            return urlParams.get('lang');
        }

        // Get from cookie
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'language') {
                return value;
            }
        }

        // Default to English
        return 'en';
    }

    setLanguage(lang) {
        // Set cookie
        document.cookie = `language=${lang}; path=/; max-age=${365 * 24 * 60 * 60}`;
        
        // Store language in session storage for dynamic updates
        sessionStorage.setItem('language', lang);
        
        // PRESERVE ALL EXISTING URL PARAMETERS
        const url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        
        // Reload page with ALL parameters intact
        window.location.href = url.toString();
    }

    toggleDropdown() {
        const switcher = document.querySelector('.language-switcher');
        if (switcher) {
            switcher.classList.toggle('active');
        }
    }

    closeDropdown() {
        const switcher = document.querySelector('.language-switcher');
        if (switcher) {
            switcher.classList.remove('active');
        }
    }

    /**
     * Render language switcher HTML
     */
    static renderSwitcher() {
        const languages = {
            'en': { name: 'English', flag: '🇬🇧', native: 'English' },
            'fr': { name: 'French', flag: '🇫🇷', native: 'Français' },
            'rw': { name: 'Kinyarwanda', flag: '🇷🇼', native: 'Ikinyarwanda' },
            'sw': { name: 'Swahili', flag: '🇹🇿', native: 'Kiswahili' }
        };

        const currentLang = new LanguageSwitcher().currentLang;
        const current = languages[currentLang] || languages['en'];

        let html = `
            <div class="language-switcher">
                <div class="language-current" onclick="languageSwitcher.toggleDropdown()">
                    <span class="language-flag">${current.flag}</span>
                    <span class="language-name">${current.native}</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="language-dropdown">
        `;

        for (const [code, lang] of Object.entries(languages)) {
            const isActive = code === currentLang ? 'active' : '';
            html += `
                <a href="javascript:void(0)" onclick="languageSwitcher.setLanguage('${code}')" class="language-option ${isActive}">
                    <span class="language-option-flag">${lang.flag}</span>
                    <div class="language-option-text">
                        <div class="language-option-native">${lang.native}</div>
                        <div class="language-option-name">${lang.name}</div>
                    </div>
                    ${isActive ? '<i class="fas fa-check"></i>' : ''}
                </a>
            `;
        }

        html += `
                </div>
            </div>
        `;

        return html;
    }
}

// Initialize global instance
const languageSwitcher = new LanguageSwitcher();
