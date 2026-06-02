<?php
/**
 * WebEngine CMS
 * https://webenginecms.org/
 * 
 * @version 1.2.6
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2025, All Rights Reserved
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
<!-- Inline styles cleaned - all styles moved to mupga-theme.css -->
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
                                <a class="site-menu__link site-menu__nav-button <?php echo (isset($_REQUEST['page']) && $_REQUEST['page'] == 'rankings') ? 'active' : ''; ?>" href="<?php echo __BASE_URL__; ?>rankings/"><i class="fas fa-users"></i> <?php echo lang('menu_txt_10'); ?></a>
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

<main class="main">

<nav class="main-nav" style="position: sticky !important; top: 0; z-index: 9999; background: #080611;">

                <button class="site-nav__menu-close button"></button>
                <ul class="site-nav__list">
                    <li class="site-nav__item">
                        <button class="modal__field-label selectdiv account-modal-trigger" type="button" style="visibility: hidden; background: none; border: none; cursor: pointer; padding: 0; width: 100%; text-align: left;"><span class="copyright22">Server: <b>MuOnline</b></span></button></li>
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
                        <button class="modal__field-label selectdiv account-modal-trigger" type="button" style="background: none; border: none; cursor: pointer; padding: 0; width: 100%; text-align: left;"><span class="copyright22">Server: <b>MuOnline</b></span></button>                    </li>
                    <li class="site-nav__item">
                        <a href="/privacy" class="site-nav__link"> <?php echo lang('module_titles_txt_24'); ?> </a>
                    </li>
                    <li class="site-nav__item">
                        <a href="/tos" class="site-nav__link"> <?php echo lang('module_titles_txt_9'); ?> </a>
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

					                                </div>            </div>
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

<?php if(isLoggedIn()): ?>
<div class="modal" id="account-modal">
    <div class="sign-modal">
        <div class="modal__block sign-modal__form">
            <button class="modal__close-button button close-account-modal" type="button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="11.5" stroke="#AFAFAF"/>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M7.56058 7.14645C7.36532 6.95118 7.04874 6.95118 6.85348 7.14645C6.65822 7.34171 6.65822 7.65829 6.85348 7.85355L11.1464 12.1465L6.14645 17.1464C5.95118 17.3417 5.95118 17.6583 6.14645 17.8536C6.34171 18.0488 6.65829 18.0488 6.85355 17.8536L11.8535 12.8536L16.753 17.753C16.9482 17.9483 17.2648 17.9483 17.4601 17.753C17.6553 17.5578 17.6553 17.2412 17.4601 17.0459L12.5606 12.1465L16.753 7.95406C16.9483 7.7588 16.9483 7.44221 16.753 7.24695C16.5578 7.05169 16.2412 7.05169 16.0459 7.24695L11.8535 11.4394L7.56058 7.14645Z" fill="#AFAFAF"/>
                </svg>
            </button>
            <h2 class="modal__header"><?php echo lang("usercp_menu_title") ?: 'Painel de Controle'; ?></h2>
            <div class="account-modal__body">
                <?php
                ob_start();
                templateBuildUsercp();
                $usercp_html = ob_get_clean();
                
                $usercp_html = str_replace(
                    '<a ',
                    '<a class="user-info__menu-link accountinner__hfr" ',
                    $usercp_html
                );
                
                echo '<div class="account-page__user-info accountinner">';
                    echo '<div class="user-info__menu accountinner__links">';
                        echo $usercp_html;
                        echo '<a href="'.__BASE_URL__.'logout" class="user-info__menu-link accountinner__hfr">';
                            echo lang("menu_txt_6");
                        echo '</a>';
                    echo '</div>';
                echo '</div>';
                ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
        


<?php
$heroSeason   = config('server_info_season') ?: 'Season 6';
$heroExp      = config('server_info_exp')    ?: '50';
$heroDrop     = config('server_info_drop')   ?: '30';
$heroOnline   = isset($onlinePlayers) ? intval($onlinePlayers) : 0;
?>

<!-- Partículas globales de fondo -->
<div class="mupga-global-particles" id="globalParticles"></div>

<section class="hero" id="hero">
    <div class="mupga-hero">
        <div class="mupga-hero__bg"></div>
        <div class="mupga-hero__scan"></div>
        <div class="mupga-hero__particles" id="heroParticles"></div>

        <div class="mupga-hero__content">
            <!-- Ornamento superior -->
            <div class="mupga-hero__ornament">
                <div class="mupga-hero__ornament-line"></div>
                <span class="mupga-hero__ornament-gem">&#10022; Mu Online &#10022;</span>
                <div class="mupga-hero__ornament-line mupga-hero__ornament-line--right"></div>
            </div>

            <h1 class="mupga-hero__title">Mu <span>PGA</span></h1>
            <p class="mupga-hero__subtitle"><?php echo htmlspecialchars($heroSeason); ?> &nbsp;&middot;&nbsp; Argentina</p>

            <div class="mupga-hero__rune-sep"><span>&#10022; &#10022; &#10022;</span></div>

            <div class="mupga-hero__stats">
                <div class="mupga-hero__stat">
                    <span class="mupga-hero__stat-value"><?php echo htmlspecialchars($heroExp); ?>x</span>
                    <span class="mupga-hero__stat-label">Experience</span>
                </div>
                <div class="mupga-hero__stat">
                    <span class="mupga-hero__stat-value"><?php echo htmlspecialchars($heroDrop); ?>x</span>
                    <span class="mupga-hero__stat-label">Drop Rate</span>
                </div>
                <div class="mupga-hero__stat">
                    <span class="mupga-hero__stat-value"><?php echo $heroOnline; ?></span>
                    <span class="mupga-hero__stat-label">Online</span>
                </div>
            </div>

            <div class="mupga-hero__cta">
                <?php if(!isLoggedIn()): ?>
                <a class="mupga-btn-primary" href="<?php echo __BASE_URL__; ?>register">
                    <i class="fas fa-user-plus"></i> <?php echo lang('menu_txt_3'); ?>
                </a>
                <?php endif; ?>
                <a class="mupga-btn-secondary" href="<?php echo __BASE_URL__; ?>downloads">
                    <i class="fas fa-download"></i> <?php echo lang('menu_txt_7'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Barra de estado -->
    <div class="mupga-hero__statusbar">
        <div class="mupga-hero__status-item">
            <div class="mupga-hero__status-dot"></div>
            <span>Servidor&nbsp;<span class="mupga-hero__status-val">Online</span></span>
        </div>
        <div class="mupga-hero__status-sep"></div>
        <div class="mupga-hero__status-item">
            <span>Versi&oacute;n:&nbsp;<span class="mupga-hero__status-val"><?php echo htmlspecialchars($heroSeason); ?></span></span>
        </div>
        <div class="mupga-hero__status-sep"></div>
        <div class="mupga-hero__status-item">
            <span>Jugadores:&nbsp;<span class="mupga-hero__status-val"><?php echo $heroOnline; ?></span></span>
        </div>
        <div class="mupga-hero__status-sep"></div>
        <div class="mupga-hero__status-item">
            <a href="<?php echo config('social_link_discord',true); ?>" target="_blank" style="color:var(--mu-gold);text-decoration:none;font-weight:700;letter-spacing:1px;">
                <i class="fab fa-discord" style="margin-right:5px;"></i>Discord
            </a>
        </div>
    </div>
</section>

<script>
(function(){
    // Hero particles
    var hp = document.getElementById('heroParticles');
    if(hp){ 
        var c=['#a855f7','#7c3fc4','#c8a84b','#6d28d9','#ddd6fe','#f0d080']; 
        for(var i=0;i<30;i++){
            var p=document.createElement('div');
            p.className='mupga-hero__particle';
            p.style.setProperty('--hx',(Math.random()*100)+'%');
            p.style.setProperty('--hd',(5+Math.random()*7)+'s');
            p.style.setProperty('--hdelay',(Math.random()*7)+'s');
            p.style.background=c[Math.floor(Math.random()*c.length)];
            var sz=(Math.random()>0.6?3:2)+'px';
            p.style.width=sz;
            p.style.height=sz;
            hp.appendChild(p);
        } 
    }
    
    // Global particles with special effects
    var gp = document.getElementById('globalParticles');
    if(gp){ 
        var c2=['#a855f7','#7c3fc4','#c8a84b','#6d28d9','#ddd6fe']; 
        var n=window.innerWidth>768?45:18; 
        for(var j=0;j<n;j++){
            var q=document.createElement('div');
            q.className='mupga-global-particle';
            q.style.setProperty('--hx',(Math.random()*100)+'%');
            q.style.setProperty('--hd',(8+Math.random()*12)+'s');
            q.style.setProperty('--hdelay',(Math.random()*10)+'s');
            q.style.background=c2[Math.floor(Math.random()*c2.length)];
            var sz2=(Math.random()>0.7?3:2)+'px';
            q.style.width=sz2;
            q.style.height=sz2;
            gp.appendChild(q);
        }
        
        // Special particles that appear occasionally
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
        
        // Rare starburst effect
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

        <section class="main-servers" id="main-servers">
            <div class="wrapper">
                <div class="main-servers__content">
                                        <div class="main-servers__top main-servers__top--topplayers">
                        <div class="account-page__top-players top-players topplayers">
                            <span class="top-players__header rightbar__title topplayers__title">
                                <span>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#FFD700" stroke="#FFA500" stroke-width="1.5"/>
                                        <path d="M12 6L13.5 9.5L17.5 10L14.5 12.5L15.5 16.5L12 14.5L8.5 16.5L9.5 12.5L6.5 10L10.5 9.5L12 6Z" fill="#FFA500"/>
                                    </svg>
                                    TOP
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle; margin-left: 8px;">
                                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#FFD700" stroke="#FFA500" stroke-width="1.5"/>
                                        <path d="M12 6L13.5 9.5L17.5 10L14.5 12.5L15.5 16.5L12 14.5L8.5 16.5L9.5 12.5L6.5 10L10.5 9.5L12 6Z" fill="#FFA500"/>
                                    </svg>
                                </span>
                            </span>

                            <div class="account-page__switch topblockbtn">
                                <a href="#blocktop1" class="account-page__switch-item account-page__switch-item--active switch_stat__item2 switch_stat__item_active2">
                                    <?php echo lang('rankings_txt_2'); ?>
                                </a>
                                <a href="#blocktop2" class="account-page__switch-item switch_stat__item2">
                                    <?php echo lang('rankings_txt_4'); ?>
                                </a>
                            </div>

                            <?php
                            loadModuleConfigs('rankings');
                            ?>

                            <?php
                            try {

                                if(!mconfig('rankings_enable_resets') || !mconfig('active')) {
                                    throw new Exception(lang('error_44', true));
                                }

                                $ranking_data = LoadCacheData('rankings_resets.cache');
                                if(!is_array($ranking_data)) {
                                    throw new Exception(lang('error_58', true));
                                }

                                $onlineCharacters = array();
                                if(mconfig('show_online_status')) {
                                    $onlineCharacters = loadCache('online_characters.cache');
                                    if(!is_array($onlineCharacters)) {
                                        $onlineCharacters = array();
                                    }
                                }

                                $Character = new Character();
                                $maxPlayers = 7;
                                $topResets = array_slice($ranking_data, 0, $maxPlayers + 1);

                                echo '<div class="top-players__table top-players__table--active topblock" id="blocktop1">';

                                    echo '<div class="top-players__table-head topplayers__head">';
                                        echo '<span class="top-players__table-head-item top-players__table-num topplayers__num">#</span>';
                                        echo '<span class="top-players__table-head-item top-players__table-ava topplayers__ava"></span>';
                                        echo '<span class="top-players__table-head-item top-players__table-name topplayers__name">'.lang('rankings_txt_10').'</span>';
                                        echo '<span class="top-players__table-head-item top-players__table-lvl topplayers__lvl">'.lang('rankings_txt_12').'</span>';
                                        echo '<span class="top-players__table-head-item top-players__table-reset topplayers__reset">'.lang('rankings_txt_13').'</span>';
                                    echo '</div>';

                                    $place = 1;
                                    foreach($topResets as $key => $row) {
                                        if($key == 0) continue;
                                        if($place > $maxPlayers) break;

                                        $characterIMG = $Character->GenerateCharacterClassAvatar($row[1], false, false);

                                        $onlineStatus = '';
                                        if(mconfig('show_online_status')) {
                                            $onlineStatus = in_array($row[0], $onlineCharacters)
                                                ? '<img src="'.__PATH_ONLINE_STATUS__.'" class="online-status-indicator"/>'
                                                : '<img src="'.__PATH_OFFLINE_STATUS__.'" class="online-status-indicator"/>';
                                        }

                                        echo '<div class="top-players__table-body topplayers__body">';
                                            echo '<div class="top-players__table-body-row topplayers__el">';

                                                echo '<span class="top-players__table-body-item top-players__table-body-num topplayers__num">'
                                                    .$place.'.</span>';

                                                echo '<span class="top-players__table-body-item top-players__table-body-ava topplayers__ava">';
                                                    echo '<img src="'.$characterIMG.'" width="20" height="20" class="rounded_corners_image" />';
                                                echo '</span>';

                                                echo '<span class="top-players__table-body-item top-players__table-body-name topplayers__name">'
                                                    .playerProfile($row[0]).$onlineStatus.
                                                '</span>';

                                                echo '<div class="top-players__table-body-stats">';
                                                    echo '<span class="top-players__table-body-item top-players__table-body-lvl selection-text--light-blue topplayers__lvl">'
                                                        .number_format($row[3]).
                                                    '</span>';
                                                    echo '<span class="top-players__table-body-item top-players__table-body-reset selection-text--light-blue topplayers__reset">'
                                                        .number_format($row[2]).
                                                    '</span>';
                                                echo '</div>';

                                            echo '</div>';
                                        echo '</div>';

                                        $place++;
                                    }

                                echo '</div>';

                            } catch(Exception $ex) {
                                echo '<div class="top-players__table top-players__table--active topblock" id="blocktop1">';
                                    echo '<p style="padding:8px 10px;font-size:12px;color:#ccc;">'
                                        .htmlspecialchars($ex->getMessage()).
                                    '</p>';
                                echo '</div>';
                            }
                            ?>

                            <?php
                            try {

                                if(!mconfig('rankings_enable_guilds') || !mconfig('active')) {
                                    throw new Exception(lang('error_44', true));
                                }

                                $guilds_data = LoadCacheData('rankings_guilds.cache');
                                if(!is_array($guilds_data)) {
                                    throw new Exception(lang('error_58', true));
                                }

                                $onlineCharacters = array();
                                if(mconfig('show_online_status')) {
                                    $onlineCharacters = loadCache('online_characters.cache');
                                    if(!is_array($onlineCharacters)) {
                                        $onlineCharacters = array();
                                    }
                                }

                                $multiplier = mconfig('guild_score_formula') == 1 ? 1 : mconfig('guild_score_multiplier');

                                $maxGuilds = 5;
                                $topGuilds = array_slice($guilds_data, 0, $maxGuilds + 1);

                                echo '<div class="top-players__table topblock" id="blocktop2">';
                                    echo '<div class="top-players__table-head topplayers__head">';
                                        echo '<span class="top-players__table-head-item top-players__table-num topplayers__num">#</span>';
                                        echo '<span class="top-players__table-head-item top-players__table-ava topplayers__ava"></span>';
                                        echo '<span class="top-players__table-head-item top-players__table-name topplayers__name">'.lang('rankings_txt_17').'</span>';
                                        echo '<span class="top-players__table-head-item top-players__table-lvl topplayers__lvl">'.lang('rankings_txt_19').'</span>';
                                    echo '</div>';

                                    $place = 1;
                                    foreach($topGuilds as $key => $gdata) {
                                        if($key == 0) continue;
                                        if($place > $maxGuilds) break;

                                        $score = number_format(floor($gdata[2] * $multiplier));
                                        $logo  = returnGuildLogo($gdata[3], 40);

                                        $onlineStatus = '';
                                        if(mconfig('show_online_status')) {
                                            $onlineStatus = in_array($gdata[1], $onlineCharacters)
                                                ? '<img src="'.__PATH_ONLINE_STATUS__.'" class="online-status-indicator"/>'
                                                : '<img src="'.__PATH_OFFLINE_STATUS__.'" class="online-status-indicator"/>';
                                        }

                                        echo '<div class="top-players__table-body topplayers__body">';
                                            echo '<div class="top-players__table-body-row topplayers__el">';

                                                echo '<span class="top-players__table-body-item top-players__table-body-num topplayers__num" style="margin-right: 37px;">'
                                                    .$place.'.</span>';

                                                echo '<span class="top-players__table-body-item top-players__table-body-ava topplayers__ava">'
                                                    .$logo.
                                                '</span>';

                                                echo '<span class="top-players__table-body-item top-players__table-body-name topplayers__name" style="width: 138px; margin-left: 45px;">'
                                                    .guildProfile($gdata[0]).
                                                '</span>';

                                                echo '<div class="top-players__table-body-stats">';
                                                    echo '<span class="top-players__table-body-item top-players__table-body-lvl selection-text--light-blue topplayers__lvl">'
                                                        .$score.
                                                    '</span>';
                                                echo '</div>';

                                            echo '</div>';
                                        echo '</div>';

                                        $place++;
                                    }

                                echo '</div>';

                            } catch(Exception $ex) {
                                echo '<div class="top-players__table topblock" id="blocktop2">';
                                    echo '<p style="padding:8px 10px;font-size:12px;color:#ccc;">'
                                        .htmlspecialchars($ex->getMessage()).
                                    '</p>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <ul class="main-servers__list main-servers__list--stack">
                        
                           <li class="main-servers__item">
    <div class="main-servers__item-wrapper main-servers__link main-servers__link--active main-servers__link1">
        <span class="main-servers__title">MuOnline</span>
        <div class="main-servers__procentbar procentbar-server">
            <span class="procentbar-server_num"><?php echo number_format($onlinePlayers); ?>%</span>
            <div class="procentbar-server__progress">
                <span class="progress" style="max-width: <?php echo number_format($onlinePlayers); ?>%;"></span>
            </div>
        </div>
        <div class="main-servers__item-bottom">
            <img src="<?php echo __PATH_TEMPLATE_IMG__; ?>server1.png" alt="">
            <div class="main-servers__info">
                <?php if(check_value(config('maximum_online', true))): ?>
                <div class="main-servers__online">
                    <?php echo lang('sidebar_srvinfo_txt_5'); ?>: <?php echo number_format($onlinePlayers); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</li>
                        
                            
                            <li class="main-servers__item main-servers__item--castle">
                                <?php templateCastleSiegeWidget(); ?>
                            </li>
                                            </ul>
                </div>
            </div>
        </section>

        <div class="main-new">
            <div class="wrapper">
                <div class="main-new__content">
 <section class="main-new__left main-leftbar">
    <div class="main-leftbar__trailer">

    </div>

    <div class="main-leftbar__news">
        <div class="news-card">
            <span class="news-card__header"><?php echo lang('news_txt_4', true) ?: 'Notícias'; ?></span>
            <div class="news-card__body">
        <div class="main-news">
            
            <div class="newsmodule">

<?php
// Função auxiliar para construir URL de paginação
if (!function_exists('buildPaginationUrl')) {
    function buildPaginationUrl($page) {
        $params = $_GET;
        // Remover 'page' se existir para evitar conflito com roteamento
        unset($params['page']);
        
        if ($page == 1) {
            unset($params['news_page']);
        } else {
            $params['news_page'] = $page;
        }
        $queryString = !empty($params) ? '?' . http_build_query($params) : '';
        return __BASE_URL__ . $queryString;
    }
}

$cachedNews = loadCache('news.cache');

if (is_array($cachedNews)) {

    // Configuração de paginação
    $itemsPerPage = 5;
    $currentPage = isset($_GET['news_page']) && is_numeric($_GET['news_page']) && $_GET['news_page'] > 0 ? (int)$_GET['news_page'] : 1;
    $totalNews = count($cachedNews);
    $totalPages = ceil($totalNews / $itemsPerPage);
    
    // Garantir que a página atual não exceda o total de páginas
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }
    
    // Calcular offset
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    $currentLang = null;
    if (config('language_switch_active', true) && isset($_SESSION['language_display'])) {
        $currentLang = $_SESSION['language_display'];
    }

    // Mostrar apenas as notícias da página atual
    $displayedCount = 0;
    foreach ($cachedNews as $index => $newsArticle) {
        // Pular notícias até chegar no offset
        if ($index < $offset) continue;
        
        // Parar após mostrar 5 notícias
        if ($displayedCount >= $itemsPerPage) break;

        $news_id   = $newsArticle['news_id'];
        $news_url  = __BASE_URL__ . 'news/' . $news_id . '/';
        $news_date = date("d/m/Y", $newsArticle['news_date']);

        $news_title = base64_decode($newsArticle['news_title']);

        if (
            $currentLang &&
            isset($newsArticle['translations']) &&
            is_array($newsArticle['translations']) &&
            array_key_exists($currentLang, $newsArticle['translations'])
        ) {
            $news_title = base64_decode($newsArticle['translations'][$currentLang]);
        }

        echo '<div class="newsmodule__item">';

            // TÍTULO (Notice)
            echo '<span class="newsmodule__title">';
                echo strtok(htmlspecialchars($news_title), ' ');
            echo '</span>';

            // CUERPO
            echo '<div class="newsmodule__body">';

                echo '<ul class="newsmodule__info newsmodule__info-list list-reset flex-c">';
                    // Etiqueta tipo (Notice)
                    echo '<li class="newsmodule__info-item"><a href="' . $news_url . '">'.htmlspecialchars($news_title).'</a></li>';
                    // Fecha
                    echo '<li class="newsmodule__info-item">';
                        echo 'Date: <span class="selection-text--light-blue">'.$news_date.'</span>';
                    echo '</li>';
                echo '</ul>';

            echo '</div>';

        echo '</div>';

        $displayedCount++;
    }
    
    // Exibir controles de paginação se houver mais de uma página
    if ($totalPages > 1) {
        echo '<div class="news-pagination" style="margin-top: 30px; display: flex; justify-content: center;">';
        echo '<ul class="pagination__list">';
        
        // Botão Anterior
        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            echo '<li class="pagination__arrow pagination__arrow-left">';
            echo '<a href="' . buildPaginationUrl($prevPage) . '" class="pagination__link">‹</a>';
            echo '</li>';
        }
        
        // Números das páginas
        $maxPagesToShow = 5;
        $startPage = max(1, $currentPage - floor($maxPagesToShow / 2));
        $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);
        
        if ($startPage > 1) {
            echo '<li><a href="' . buildPaginationUrl(1) . '" class="pagination__link">1</a></li>';
            if ($startPage > 2) {
                echo '<li class="pagination__item--dots"><span class="pagination__dots"></span></li>';
            }
        }
        
        for ($page = $startPage; $page <= $endPage; $page++) {
            $activeClass = $page == $currentPage ? ' active' : '';
            echo '<li><a href="' . buildPaginationUrl($page) . '" class="pagination__link' . $activeClass . '">' . $page . '</a></li>';
        }
        
        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                echo '<li class="pagination__item--dots"><span class="pagination__dots"></span></li>';
            }
            echo '<li><a href="' . buildPaginationUrl($totalPages) . '" class="pagination__link">' . $totalPages . '</a></li>';
        }
        
        // Botão Próxima
        if ($currentPage < $totalPages) {
            $nextPage = $currentPage + 1;
            echo '<li class="pagination__arrow pagination__arrow-right">';
            echo '<a href="' . buildPaginationUrl($nextPage) . '" class="pagination__link">›</a>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
}
?>
            </div>
            </div>
        </div>
        </div>
    </div>
</section>
<!-- Sidebar with Server Info -->
<section class="main-new__right main-rightbar">
    <?php include(__PATH_TEMPLATE_ROOT__ . 'inc/modules/sidebar.php'); ?>
</section>

                </div>
            </div>
        </div>   
                                        <!-- main-slider.php  -->
    </main> 
<footer class="footer">
        <?php include(__PATH_TEMPLATE_ROOT__ . 'inc/modules/footer.php'); ?>
     </footer>
<script>
(function() {
    var elDays = document.getElementById("days");
    var elHours = document.getElementById("hours");
    var elMinutes = document.getElementById("minutes");
    var elSeconds = document.getElementById("seconds");
    if (!elDays || !elHours || !elMinutes || !elSeconds) return;

    var countDownDate = new Date("2025-11-03T17:00:00").getTime();
    var interval = setInterval(function() {
        var now = new Date().getTime();
        var distance = countDownDate - now;
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        elDays.innerHTML = days;
        elHours.innerHTML = hours;
        elMinutes.innerHTML = minutes;
        elSeconds.innerHTML = seconds;

        if (distance < 0) {
            clearInterval(interval);
            elDays.innerHTML = "0";
            elHours.innerHTML = "0";
            elMinutes.innerHTML = "0";
            elSeconds.innerHTML = "0";
        }
    }, 1000);
})();
</script>
</body>
<!-- RIPEO CARTMAN-->
</html>