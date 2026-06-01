document.addEventListener('DOMContentLoaded', function() {

    //язык
    let languageButton = document.querySelector('.language');
    let languageList = document.querySelector('.language__list');

    languageButton.addEventListener('click', function() {
        languageList.classList.toggle('active');
        languageButton.classList.toggle('active');
    });

    document.addEventListener('click', function(e) {
        if (!languageButton.contains(e.target) && !languageList.contains(e.target)) {
            languageList.classList.remove('active');
        }
    });

    //Слайдер внутренний
    function updateSliderClasses() {
        let mainSlider = document.querySelector('.slider__list');

        if(mainSlider) {
            mainSlider.classList.add('main__slider');
        }
        initSlick();
    }

    // Функция для инициализации слайдера (só executa se jQuery e Slick estiverem carregados)
    function initSlick() {
        if (typeof $ === 'undefined' || typeof $.fn.slick !== 'function') return;
        var $slider = $('.main__slider').not('.slick-initialized');
        if (!$slider.length) return;
        $slider.slick({
            infinite: false,
            dots: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            fade: true,
            cssEase: 'linear',
            prevArrow: '<button class="slider__arrow slider__arrow--left left-arrow button" type="button">' +
                            '<svg width="17" height="32" viewBox="0 0 17 32" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                                '<path fill-rule="evenodd" clip-rule="evenodd" d="M15.0148 31.1273C15.4053 31.5178 16.0385 31.5178 16.429 31.1273C16.8195 30.7367 16.8195 30.1036 16.429 29.7131L2.78685 16.0709L16.429 2.42874C16.8196 2.03822 16.8196 1.40505 16.429 1.01453C16.0385 0.624004 15.4053 0.624004 15.0148 1.01453L0.872682 15.1567C0.62446 15.4049 0.534011 15.7511 0.601335 16.0708C0.533927 16.3905 0.624362 16.7369 0.872637 16.9851L15.0148 31.1273Z" fill="#6BD5FC"/>' +
                            '</svg>' +
                        '</button>',
            nextArrow: '<button class="slider__arrow slider__arrow--right right-arrow button" type="button">' +
                            '<svg width="17" height="32" viewBox="0 0 17 32" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                                '<path fill-rule="evenodd" clip-rule="evenodd" d="M1.98523 0.872727C1.59471 0.482203 0.96154 0.482203 0.571016 0.872727C0.180492 1.26325 0.180492 1.89642 0.571016 2.28694L14.2132 15.9291L0.570969 29.5713C0.180446 29.9618 0.180445 30.5949 0.570969 30.9855C0.961494 31.376 1.59466 31.376 1.98518 30.9855L16.1273 16.8433C16.3755 16.5951 16.466 16.2489 16.3987 15.9292C16.4661 15.6095 16.3756 15.2631 16.1274 15.0149L1.98523 0.872727Z" fill="#6BD5FC"/>' +
                            '</svg>' +
                        '</button>'
        });
    }
    
    $(window).on('load', function() {
        updateSliderClasses();

        var slideImage = $('.slider__item img');
    
        if (slideImage.length) {
            var imageHeight = slideImage.height();
            
            var slickDots = $('.slick-dots');
            
            if (slickDots.length) {
                slickDots.css('top', (imageHeight + 1) + 'px');
            }
        }
    });    

    //табы
    let tabsButtons = document.querySelectorAll('.tab-links__link');
    let navButtons = document.querySelectorAll('.nav-buttons__link');
    let tabsContent = document.querySelectorAll('.tab-content');

    if (tabsButtons.length > 0 && tabsContent.length > 0) {
        function showContent(index) {
            tabsContent.forEach(content => {
                content.classList.remove('active');
                if (typeof $.fn.slick === 'function') {
                    $(content).find('.slick-initialized').each(function() {
                        $(this).slick('unslick');
                    });
                }
            });
            tabsButtons.forEach(tab => tab.classList.remove('active'));
            tabsContent[index].classList.add('active');
            tabsButtons[index].classList.add('active');
            initSlickOnVisibleContent(tabsContent[index]);
        }
        
        tabsButtons.forEach((tab, index) => {
            tab.addEventListener('click', () => showContent(index));
        });
        
        showContent(0);
    }
    
    function initSlickOnVisibleContent(content) {
        const characterSliderLists = content.querySelectorAll('.character-types__list.character-types__slider');
    
        if (characterSliderLists.length > 0) {
            characterSliderLists.forEach(list => {
                if (!$(list).hasClass('slick-initialized')) {
                    initSlick(); 
                }
            });
        }
    }

    if(navButtons.length > 0 && tabsContent.length > 0) {
        function showContent(index) {
            tabsContent.forEach(content => content.classList.remove('active'));
            navButtons.forEach(tab => tab.classList.remove('active'));
            tabsContent[index].classList.add('active');
            navButtons[index].classList.add('active');
        }
        
        navButtons.forEach((tab, index) => {
            tab.addEventListener('click', () => showContent(index));
        });
        
        showContent(0);
    }

    //модалки
    let loginButtons = document.querySelectorAll('.login-button');
    let modalCloseButtons = document.querySelectorAll('.close-modal');
    let signModal = document.querySelector('.sign-modal');
    let modals = document.querySelectorAll('.modal');

    function openModal(modalElement) {
        let parentModal = modalElement.closest('.modal');
        if (parentModal) {
            parentModal.classList.add('active');
            modalElement.classList.add('active');
        }
    }
    
    function closeModal() {
        modals.forEach(modal => {
            modal.classList.remove('active');
            signModal.classList.remove('active');
        });
    }
    
    loginButtons.forEach((button) => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            openModal(signModal);
        });
    });

    // Account Modal - Mesmo comportamento do modal de login
    let accountModalTriggers = document.querySelectorAll('.account-modal-trigger');
    let accountModal = document.getElementById('account-modal');
    let closeAccountModalButtons = document.querySelectorAll('.close-account-modal');
    let accountSignModal = accountModal ? accountModal.querySelector('.sign-modal') : null;

    function openAccountModal() {
        if (accountModal && accountSignModal) {
            accountModal.classList.add('active');
            accountSignModal.classList.add('active');
        }
    }
    
    function closeAccountModal() {
        if (accountModal && accountSignModal) {
            accountModal.classList.remove('active');
            accountSignModal.classList.remove('active');
        }
    }

    if (accountModal && accountModalTriggers.length > 0) {
        accountModalTriggers.forEach((button) => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openAccountModal();
            });
        });

        closeAccountModalButtons.forEach((button) => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                closeAccountModal();
            });
        });

        // Fechar modal ao clicar fora
        if (accountModal) {
            accountModal.addEventListener('click', function(e) {
                if (e.target === accountModal) {
                    closeAccountModal();
                }
            });
        }
    }

    modalCloseButtons.forEach((button) => {
    button.addEventListener('click', function(e) {
        e.stopPropagation();
        closeModal();
        });
    });
    
    document.addEventListener('click', function(e) {
        let activeModalBlock = document.querySelector('.modal.active .modal__block');
        if (activeModalBlock && !activeModalBlock.contains(e.target)) {
            closeModal();
        }
    });

    //мобильное меню 
    let siteNav = document.querySelector('.site-nav');
    let siteNavOpen = document.querySelector('.user-nav__menu-open');
    let siteNavClose = document.querySelectorAll('.site-nav__menu-close');
    let siteNavMobile = document.querySelector('.site-nav__mobile');

    function isMobileScreen() {
        return window.matchMedia('(max-width: 768px)').matches;
    }

    function toggleMenu() {
        if (isMobileScreen()) {
            siteNavMobile.classList.toggle('active');
        } else {
            siteNav.classList.toggle('active');
        }
    }

    siteNavOpen.addEventListener('click', toggleMenu);

    siteNavClose.forEach(button => {
        button.addEventListener('click', function() {
            siteNav.classList.remove('active');
            siteNavMobile.classList.remove('active');
        });
    });

    document.addEventListener('click', function(event) {
        if (isMobileScreen()) {
            if (!siteNavMobile.contains(event.target) && !siteNavOpen.contains(event.target)) {
                siteNavMobile.classList.remove('active');
            }
        } else {
            if (!siteNav.contains(event.target) && !siteNavOpen.contains(event.target)) {
                siteNav.classList.remove('active');
            }
        }
    });

    $(document).ready(function(){      
        // Действия по умолчанию
        $(".statistics__card").hide(); // скрыть весь контент
        $(".switch_stat__item:first").addClass("switch_stat__item_active").show(); // Активировать первую вкладку
        $(".statistics__card:first").show(); // Показать контент первой вкладки

        // Событие по клику
        $(".switch_stat__item").click(function() {
            $(".switch_stat__item").removeClass("account-page__switch-item--active"); // Удалить "active" класс
            $(this).addClass("account-page__switch-item--active"); // Добавить "active" для выбранной вкладки
            $(".statistics__card").hide(); // Скрыть контент всех вкладок
            var activeTab = $(this).attr("href"); // Найти значение атрибута href для определения активного контента
            $(activeTab).stop(true, true).fadeIn(500); // Показать активный контент с эффектом появления
            return false;
        }); 
          
          
        //Действия по умолчанию
        $(".topblock").hide(); //скрыть весь контент
        $(".switch_stat__item2:first").addClass("switch_stat__item_active").show(); //Активировать первую вкладку
        $(".topblock:first").show(); //Показать контент первой вкладки
        
        //Событие по клику
        $(".switch_stat__item2").click(function() {
            $(".switch_stat__item2").removeClass("account-page__switch-item--active"); //Удалить "active" класс
            $(this).addClass("account-page__switch-item--active"); //Добавить "active" для выбранной вкладки
            $(".topblock").hide(); //Скрыть контент вкладки
            var activeTab = $(this).attr("href"); //Найти значение атрибута, чтобы определить активный таб + контент
            $(activeTab).stop(true, true).fadeIn(500); //Исчезновение активного контента
            return false;    
        }); 
          
    });

    let style = document.createElement('style');
    style.innerHTML = `
        .alert-message {
            opacity: 1;
            transition: opacity 0.5s ease;
        }
        .alert-message.hide {
            opacity: 0;
        }
    `;
    document.head.appendChild(style);

    let alertMessages = document.querySelectorAll('.alert-message');
    let footer = document.querySelector('.alert-container');

    if (footer && alertMessages.length > 0) {
        alertMessages.forEach(function(alertMessage) {
            footer.appendChild(alertMessage);
            setTimeout(function() {
                alertMessage.classList.add('hide'); 
                setTimeout(function() {
                    alertMessage.remove();
                }, 500); 
            }, 5000);
        });
    }


    function toggleAccordion(header, contentId) {
        let menuLists = document.querySelectorAll('.site-menu__list, .site-menu__list-mobile');
        let content = document.getElementById(contentId);
        
        let arrow = header.querySelector('.site-menu__menu-header--arrow'); 
    
        if (content.classList.contains('active')) {
            content.classList.remove('active');
            if (arrow) arrow.classList.remove('active'); 
        } else {
            menuLists.forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.site-menu__menu-header--arrow').forEach(arrow => arrow.classList.remove('active'));
    
            content.classList.add('active');
            if (arrow) arrow.classList.add('active'); 
        }
    }
    
    if(document.querySelector('.site_menu')) {
        document.querySelector('.site_menu').addEventListener('click', function () {
            toggleAccordion(this, 'site_menu_list');
        });
        
        document.querySelector('.site_menu-mobile').addEventListener('click', function () {
            toggleAccordion(this, 'site_menu_list-mobile');
        });
        
        document.querySelector('.account_menu').addEventListener('click', function () {
            toggleAccordion(this, 'account_menu_list');
        });
        
        document.querySelector('.account_menu-mobile').addEventListener('click', function () {
            toggleAccordion(this, 'account_menu_list-mobile');
        });
    }
    
    window.addEventListener('DOMContentLoaded', function () {
        let accountMenuList = document.getElementById('account_menu_list');
        let accountMenuListMobile = document.getElementById('account_menu_list-mobile');
    
        let accountMenuArrow = document.querySelector('.account_menu .site-menu__menu-header--arrow');
        let accountMenuArrowMobile = document.querySelector('.account_menu-mobile .site-menu__menu-header--arrow');
    
        if (window.location.pathname === '/account') {
            if (accountMenuList) {
                accountMenuList.classList.add('active'); 
            }
            if (accountMenuArrow) {
                accountMenuArrow.classList.add('active'); 
            }
    
            if (accountMenuListMobile) {
                accountMenuListMobile.classList.add('active');
            }
            if (accountMenuArrowMobile) {
                accountMenuArrowMobile.classList.add('active');
            }
        }
    });

    $(document).on('click', '.main-servers__link', function() {
        const id = $(this).attr('data-session');
        if (id) {
            location = '?num=' + id;
        }
    });


});