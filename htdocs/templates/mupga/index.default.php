<?php
/**
 * WebEngine CMS
 * https://webenginecms.org/
 * 
 * @version 1.2.6
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2025 Lautaro Angelico, All Rights Reserved
 * 
 * Licensed under the MIT license
 * http://opensource.org/licenses/MIT
 */

if(!defined('access') or !access) die();

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title><?php $handler->websiteTitle(); ?></title>
		<meta name="generator" content="WebEngine <?php echo __WEBENGINE_VERSION__; ?>"/>
		<meta name="author" content="Lautaro Angelico"/>
		<meta name="description" content="<?php config('website_meta_description'); ?>"/>
		<meta name="keywords" content="<?php config('website_meta_keywords'); ?>"/>
		<meta property="og:type" content="website" />
		<meta property="og:title" content="<?php $handler->websiteTitle(); ?>" />
		<meta property="og:description" content="<?php config('website_meta_description'); ?>" />
		<meta property="og:image" content="<?php echo __PATH_IMG__; ?>webengine.jpg" />
		<meta property="og:url" content="<?php echo __BASE_URL__; ?>" />
		<meta property="og:site_name" content="<?php $handler->websiteTitle(); ?>" />
		<link rel="shortcut icon" href="<?php echo __PATH_TEMPLATE__; ?>favicon.ico"/>
	    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">	    
        <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>style.css" rel="stylesheet">
        <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>profiles.css" rel="stylesheet">
	    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>slick.css" rel="stylesheet">
	    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>rankings-class-filter.css" rel="stylesheet">
	    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>castle-siege.css" rel="stylesheet">
	    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>mupga-theme.css" rel="stylesheet">
	    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css" integrity="sha512-HK5fgLBL+xu6dm/Ii3z4xhlSUyZgTT9tuc/hSrtw6uzJOvgRr2a9jyxxT1ely+B+xFAmJKVSTbpM/CuL7qxO8w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
		<script>
			var baseUrl = '<?php echo __BASE_URL__; ?>';
		</script>
        <script type="text/javascript">console.log('Powered by Cartman / OzzY');</script>
</head>
<body class="body three-column1 lang-<?php echo isset($_SESSION['language_display']) ? $_SESSION['language_display'] : config('language_default', true); ?>">
    <header class="header">
                    <article class="site-menu">
                <div class="site-menu__wrapper">
                    <div class="logo">
                        <a class="logo__link" href="/">
                            <img class="logo__image" src="<?php echo __PATH_TEMPLATE_IMG__; ?>logo.png" width="195" height="104" alt="logo">
                        </a>
                    </div>
                    <nav class="site-menu__nav--nologin">
                        <ul class="site-menu__list">
                            <li class="site-menu__item">
                                <a class="site-menu__link site-menu__nav-button <?php echo (!isset($_REQUEST['page']) || $_REQUEST['page'] == '' || $_REQUEST['page'] == 'home') ? 'active' : ''; ?>" href="<?php echo __BASE_URL__; ?>"><i class="fas fa-home"></i> <?php echo lang('menu_txt_1'); ?></a>
                            </li>
                            <li class="site-menu__item">
                                <a class="site-menu__link site-menu__nav-button <?php echo (isset($_REQUEST['page']) && $_REQUEST['page'] == 'info') ? 'active' : ''; ?>" href="<?php echo __BASE_URL__; ?>info"><i class="fas fa-list-ol"></i> <?php echo lang('module_titles_txt_17'); ?></a>
                            </li>
                            <li class="site-menu__item">
                                <a class="site-menu__link site-menu__nav-button <?php echo (isset($_REQUEST['page']) && $_REQUEST['page'] == 'downloads') ? 'active' : ''; ?>" href="<?php echo __BASE_URL__; ?>downloads"><i class="fas fa-download"></i> <?php echo lang('menu_txt_7'); ?></a>
                            </li>
                            <li class="site-menu__item">
                                <a class="site-menu__link site-menu__nav-button <?php echo (isset($_REQUEST['page']) && $_REQUEST['page'] == 'donation') ? 'active' : ''; ?>" href="<?php echo __BASE_URL__; ?>donation"><i class="fas fa-coins"></i> <?php echo lang('menu_txt_8'); ?></a>
                            </li>
                            <li class="site-menu__item">
                                <a class="site-menu__link site-menu__nav-button <?php echo (isset($_REQUEST['page']) && strpos($_REQUEST['page'],'rankings') === 0) ? 'active' : ''; ?>" href="<?php echo __BASE_URL__; ?>rankings/"><i class="fas fa-users"></i> <?php echo lang('menu_txt_10'); ?></a>
                            </li>
                            <li class="site-menu__item">
                                <a class="site-menu__link site-menu__nav-button <?php echo (isset($_REQUEST['page']) && $_REQUEST['page'] == 'contact') ? 'active' : ''; ?>" href="<?php echo __BASE_URL__; ?>contact"><i class="fas fa-envelope"></i> <?php echo lang('module_titles_txt_26'); ?></a>
                            </li>
                        </ul>

                        <!-- Bloque de sesion dinamico -->
                        <div class="mu-session-block">
                        <?php if(!isLoggedIn()): ?>
                            <button class="mu-btn-login login-button" type="button"><i class="fas fa-sign-in-alt"></i> <?php echo lang('menu_txt_4'); ?></button>
                            <a class="mu-btn-register" href="<?php echo __BASE_URL__; ?>register"><i class="fas fa-user-plus"></i> <?php echo lang('menu_txt_3'); ?></a>
                        <?php else: ?>
                            <a class="mu-btn-usercp account-modal-trigger" href="#" data-modal="account-modal"><i class="fas fa-user-cog"></i> <?php echo lang('menu_txt_12'); ?></a>
                            <a class="mu-btn-logout" href="<?php echo __BASE_URL__; ?>logout"><i class="fas fa-sign-out-alt"></i> <?php echo lang('menu_txt_6'); ?></a>
                        <?php endif; ?>
                        </div>

                        <div class="site-menu__social">
                            <a class="site-menu__social-link site-menu__social-link--discord" href="<?php echo config('social_link_discord',true); ?>" target="_blank">
                                <span><i class="fab fa-discord"></i> <b>Discord</b></span>
                            </a>
                            <a class="site-menu__social-link site-menu__social-link--whatsapp" href="<?php echo config('social_link_facebook',true); ?>" target="_blank">
                                <span><i class="fab fa-whatsapp"></i> <b>WhatsApp</b></span>
                            </a>
                        </div>
                    </nav>
                </div>
            </article>

        <div class="wrapper"><div id="exception"></div></div>
    </header>

<!-- Partículas globales de fondo -->
<div class="mupga-global-particles" id="globalParticles"></div>

<main class="main main__inner">
<nav class="main-nav" style="position: sticky !important; top: 0; z-index: 9999; background: #080611;">
                <button class="site-nav__menu-close button"></button>
                <ul class="site-nav__list">
                    <li class="site-nav__item">
                        <button class="modal__field-label selectdiv" type="button" style="visibility: hidden; background: none; border: none; cursor: pointer; padding: 0; width: 100%; text-align: left;"><span class="copyright22">Server: <b>MuOnline</b></span></button></li>
                    <li class="site-nav__item site-nav__item--links">
                        <a href="/privacy" class="site-nav__link"><?php echo lang('module_titles_txt_24'); ?></a>
                        <a href="/tos" class="site-nav__link"><?php echo lang('module_titles_txt_9'); ?></a>
                    </li>
                </ul>
            </div>
            <div class="site-nav__mobile">
                <button class="site-nav__menu-close button"></button>
                <ul class="site-nav__list">
                    <li class="site-nav__item">
                        <button class="modal__field-label selectdiv" type="button" style="background: none; border: none; cursor: pointer; padding: 0; width: 100%; text-align: left;"><span class="copyright22">Server: <b>MuOnline</b></span></button></li>
                    <li class="site-nav__item">
                        <a href="/privacy" class="site-nav__link"><?php echo lang('module_titles_txt_24'); ?></a>
                    </li>
                    <li class="site-nav__item">
                        <a href="/tos" class="site-nav__link"><?php echo lang('module_titles_txt_9'); ?></a>
                    </li>
                </ul>
                
                <ul class="site-nav__list">
                </ul>

                <div class="site-menu__buttons site-menu__buttons--mobile">
                    <a class="site-menu__buttons--reg site-menu__button button register-button" href="/register"><?php echo lang('menu_txt_3'); ?></a>
                    <a class="site-menu__buttons--download-game site-menu__button button" href="/downloads"><?php echo lang('menu_txt_7'); ?></a>
                    <a class="site-menu__buttons--donate site-menu__button button" href="/donation"><?php echo lang('menu_txt_8'); ?></a>
                    <a class="site-menu__buttons--report site-menu__button button" href="/contact"><?php echo lang('module_titles_txt_26'); ?></a>
                </div>
                <div class="site-menu__social site-menu__social--molile">
                    <a class="site-menu__social-link site-menu__social-link--discord" href="<?php echo config('social_link_discord',true); ?>" target="_blank">
                        <span><i class="fab fa-discord"></i> <b>Discord</b></span>
                    </a>
                    <a class="site-menu__social-link site-menu__social-link--whatsapp" href="<?php echo config('social_link_facebook',true); ?>" target="_blank">
                        <span><i class="fab fa-whatsapp"></i> <b>WhatsApp</b></span>
                    </a>
                </div>
            </div>
            <div class="user-nav">
                <button class="user-nav__menu-open button">
                    <span class="user-nav__menu-button"></span>
                </button>
                <a class="home-button" href="/">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.66761 16H13.3324C13.7566 15.9995 14.1632 15.8308 14.4631 15.5309C14.763 15.231 14.9317 14.8244 14.9322 14.4003V7.17068L15.1061 7.32906C15.2107 7.42435 15.3488 7.47421 15.4901 7.46767C15.6314 7.46113 15.7643 7.39872 15.8596 7.29418C15.9549 7.18964 16.0047 7.05153 15.9982 6.91022C15.9917 6.76892 15.9293 6.636 15.8247 6.54071L9.1129 0.426131C8.80707 0.151753 8.41068 0 7.99981 0C7.58894 0 7.19255 0.151753 6.88672 0.426131L0.175338 6.54028C0.0707973 6.63558 0.00839283 6.76849 0.00185269 6.9098C-0.00468746 7.0511 0.0451728 7.18921 0.140465 7.29376C0.235757 7.3983 0.368674 7.4607 0.509978 7.46724C0.651281 7.47378 0.789395 7.42392 0.893936 7.32863L1.06788 7.17058V14.4003C1.06836 14.8244 1.23706 15.231 1.53696 15.5309C1.83686 15.8308 2.24348 15.9995 2.66761 16ZM2.13436 6.19901L7.60479 1.21458C7.71488 1.12049 7.85494 1.06878 7.99976 1.06878C8.14458 1.06878 8.28465 1.12049 8.39473 1.21458L13.8657 6.19901V14.4003C13.8655 14.5416 13.8093 14.6772 13.7093 14.7771C13.6094 14.8771 13.4738 14.9333 13.3324 14.9335H2.66761C2.52623 14.9333 2.3907 14.8771 2.29073 14.7771C2.19077 14.6772 2.13453 14.5416 2.13436 14.4003V6.19901Z" fill="white"/>
                    </svg>
                </a>
                <div class="language">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0001 18.3334C14.6025 18.3334 18.3334 14.6024 18.3334 10C18.3334 5.39765 14.6025 1.66669 10.0001 1.66669C5.39771 1.66669 1.66675 5.39765 1.66675 10C1.66675 14.6024 5.39771 18.3334 10.0001 18.3334Z" stroke="#F5F2FA"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0001 17.5C10.0001 17.5 13.3334 14.7727 13.3334 10C13.3334 5.22727 10.0001 2.5 10.0001 2.5C10.0001 2.5 6.66675 5.22727 6.66675 10C6.66675 14.7727 10.0001 17.5 10.0001 17.5Z" stroke="#F5F2FA"/>
                        <path d="M2.08341 7.50002H17.9167" stroke="#F5F2FA" stroke-linecap="round"/>
                        <path d="M2.08341 12.5H17.9167" stroke="#F5F2FA" stroke-linecap="round"/>
                    </svg>
                    <ul class="language__list">
                        <li class="language__item"> 
                            <?php if(config('language_switch_active',true)) templateLanguageSelector(); ?>
                        </li>
                    </ul>
                </div>
                
<?php if(!isLoggedIn()): ?>
    <button class="user-nav__login-button button login-button" type="button">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M15.7528 9.00004L12.0012 5.24847" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12.0012 12.7516L15.7528 9" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M15.7529 9.00003H7.49951" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M7.4995 15.7528H5.74878C3.81497 15.7528 2.24731 14.1852 2.24731 12.2514V5.99878C2.24731 4.06497 3.81497 2.49731 5.74878 2.49731H7.4995" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <?php echo lang('menu_txt_4'); ?>
    </button>
<?php else: ?>
    <a href="<?php echo __BASE_URL__; ?>usercp" class="user-nav__login-button button login-button">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M15.7528 9.00004L12.0012 5.24847" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12.0012 12.7516L15.7528 9" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M15.7529 9.00003H7.49951" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M7.4995 15.7528H5.74878C3.81497 15.7528 2.24731 14.1852 2.24731 12.2514V5.99878C2.24731 4.06497 3.81497 2.49731 5.74878 2.49731H7.4995" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <?php echo lang('menu_txt_5'); ?>
    </a>
<?php endif; ?>
            </div>
</nav>

<?php if(!isLoggedIn()): ?>
<div class="modal">
    <div class="sign-modal">
        <div class="modal__block sign-modal__form">
            <button class="modal__close-button button close-modal" type="button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="11.5" stroke="#AFAFAF"/>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M7.56058 7.14645C7.36532 6.95118 7.04874 6.95118 6.85348 7.14645C6.65822 7.34171 6.65822 7.65829 6.85348 7.85355L11.1464 12.1465L6.14645 17.1464C5.95118 17.3417 5.95118 17.6583 6.14645 17.8536C6.34171 18.0488 6.65829 18.0488 6.85355 17.8536L11.8535 12.8536L16.753 17.753C16.9482 17.9483 17.2648 17.9483 17.4601 17.753C17.6553 17.5578 17.6553 17.2412 17.4601 17.0459L12.5606 12.1465L16.753 7.95406C16.9483 7.7588 16.9483 7.44221 16.753 7.24695C16.5578 7.05169 16.2412 7.05169 16.0459 7.24695L11.8535 11.4394L7.56058 7.14645Z" fill="#AFAFAF"/>
                </svg>
            </button>
            <h2 class="modal__header"><?php echo lang('module_titles_txt_2'); ?></h2>
            <form class="modal__form" action="<?php echo __BASE_URL__; ?>login" method="post" id="sign-modal">
                <div class="modal__field">
                    <label class="modal__field-label"><?php echo lang('login_txt_1'); ?>:
                        <input class="modal__field-input" type="text" name="webengineLogin_user" id="login_input" required>
                    </label>
                </div>
                <div class="modal__field">
                    <label class="modal__field-label"><?php echo lang('login_txt_2'); ?>:
                        <input class="modal__field-input" type="password" name="webengineLogin_pwd" id="password_input" required>
                    </label>
                </div>
                <div class="modal__captcha">
                    <div></div>
                </div>
                <button class="sign-modal__submit button modal__submit" name="webengineLogin_submit" type="submit" value="submit">
                    <?php echo lang('login_txt_3'); ?>
                </button>
            </form>
            <div class="sign-modal__tip">
                <a class="sign-modal__tip-link" href="<?php echo __BASE_URL__; ?>forgotpassword">
                    <?php echo lang('login_txt_4'); ?>
                </a>
            </div>
            <div class="sign-modal__tip">
                <a class="sign-modal__tip-link sign-modal__link-register" href="<?php echo __BASE_URL__; ?>register">
                    <?php echo lang('menu_txt_3'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<section class="mu-page-content">
    <div class="mu-page-wrapper">
        <div class="mu-content-area">
            <?php $handler->loadModule($_REQUEST['page'],$_REQUEST['subpage']); ?>
        </div>
    </div>
</section>
</main> 
<footer class="footer">
<?php include(__PATH_TEMPLATE_ROOT__ . 'inc/modules/footer.php'); ?>
    </footer>	
<script>
// Global particles for all pages with special effects
(function(){
    var gp = document.getElementById('globalParticles');
    if(gp){ 
        var colors = ['#a855f7','#7c3fc4','#c8a84b','#6d28d9','#ddd6fe']; 
        var particleCount = window.innerWidth > 768 ? 45 : 18;
        
        // Create base particles
        for(var j=0; j<particleCount; j++){
            var q = document.createElement('div');
            q.className = 'mupga-global-particle';
            q.style.setProperty('--hx', (Math.random()*100)+'%');
            q.style.setProperty('--hd', (8+Math.random()*14)+'s');
            q.style.setProperty('--hdelay', (Math.random()*12)+'s');
            q.style.background = colors[Math.floor(Math.random()*colors.length)];
            var sz = (Math.random()>0.7 ? 3 : 2)+'px';
            q.style.width = sz;
            q.style.height = sz;
            gp.appendChild(q);
        }
        
        // Special particles that appear occasionally (golden/cyan glow)
        setInterval(function(){
            if(Math.random() > 0.5){
                var special = document.createElement('div');
                special.className = 'mupga-special-particle';
                special.style.setProperty('--hx', (Math.random()*100)+'%');
                var specialColors = ['#f0d080', '#00b4d8', '#22c55e', '#f472b6'];
                special.style.background = specialColors[Math.floor(Math.random()*specialColors.length)];
                special.style.color = special.style.background;
                gp.appendChild(special);
                setTimeout(function(){ if(special.parentNode) special.remove(); }, 6000);
            }
        }, 2500);
        
        // Rare starburst effect (sparkle)
        setInterval(function(){
            if(Math.random() > 0.85){
                var star = document.createElement('div');
                star.className = 'mupga-starburst-particle';
                star.style.setProperty('--hx', (Math.random()*100)+'%');
                star.style.setProperty('--hy', (Math.random()*100)+'%');
                star.style.background = Math.random() > 0.5 ? '#f0d080' : '#a855f7';
                star.style.color = star.style.background;
                gp.appendChild(star);
                setTimeout(function(){ if(star.parentNode) star.remove(); }, 2000);
            }
        }, 5000);
        
        // Very rare meteor/shooting star
        setInterval(function(){
            if(Math.random() > 0.92){
                var meteor = document.createElement('div');
                meteor.className = 'mupga-meteor-particle';
                meteor.style.setProperty('--hx', (Math.random()*80)+'%');
                meteor.style.background = '#f0d080';
                meteor.style.color = '#f0d080';
                gp.appendChild(meteor);
                setTimeout(function(){ if(meteor.parentNode) meteor.remove(); }, 1500);
            }
        }, 8000);
    }
})();
</script>
</body>
<!-- RIPEO CARTMAN-->
</html>
