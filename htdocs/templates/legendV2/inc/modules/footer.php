        <div class="wrapper"> 
        <section class="footer__content">
            <div class="footer-info">
                    <p class="footer-info__adress"><?php echo langf('footer_copyright', array(config('server_name', true), date("Y"))); ?></p>
                                        <p class="footer-info__adress"><?php echo lang('footer_webzen_copyright'); ?></p>
                    <p class="footer-info__copyright"><?php $handler->webenginePowered(); ?> and Cartman / OzzY</span></p>
            </div>
        </section>
            <div class="alert-container"></div>
        </div>
   
		
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    <script src="<?php echo __PATH_TEMPLATE_JS__; ?>index.js"></script>
	<script src="<?php echo __PATH_TEMPLATE_JS__; ?>rankings-class-filter.js"></script>
    <script src="<?php echo __PATH_TEMPLATE_JS__; ?>events.js"></script>