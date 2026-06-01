<?php
/**
 * WebEngine CMS
 * https://webenginecms.org/
 * 
 * @version 1.1.0
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2019 Lautaro Angelico, All Rights Reserved
 * 
 * Licensed under the MIT license
 * http://opensource.org/licenses/MIT
 */

try {
	
	if(!mconfig('active')) throw new Exception(lang('error_47',true));
	
	echo '<div class="page-title"><span>'.lang('module_titles_txt_11',true).'</span></div>';

	// Párrafo explicativo
	echo '<div class="row">';
		echo '<div class="col-xs-12" style="margin-bottom: 20px;">';
			echo '<div class="panel panel-default" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: inherit;">';
				echo '<div class="panel-body">';
					echo '<p>Las <strong>WCoins</strong> son la moneda del servidor y se utilizan para acceder a ítems, beneficios y ventajas exclusivas en la tienda. Su compra es lo que nos permite mantener el servidor activo y en constante mejora. <em>Cualquier donación adicional es siempre bienvenida y muy valorada.</em></p>';
					echo '<hr style="border-color: rgba(255,255,255,0.1);">';
					echo '<div class="row text-center">';

						echo '<div class="col-xs-12 col-sm-4">';
							echo '<h4><span class="label label-success">Mercado Pago</span></h4>';
							echo '<p><strong>$1 ARS = 1 WCoin</strong><br>';
							echo 'Sin mínimo ni máximo.<br>';
							echo 'Comprá lo que quieras.</p>';
						echo '</div>';

						echo '<div class="col-xs-12 col-sm-4">';
							echo '<h4><span class="label label-warning">Binance Pay</span></h4>';
							echo '<p><strong>1 USDT = 1.000 WCoins</strong><br>';
							echo 'Sin mínimo ni máximo.<br>';
							echo 'Comprá lo que quieras.</p>';
						echo '</div>';

						echo '<div class="col-xs-12 col-sm-4">';
							echo '<h4><span class="label label-info">¿Cómo funciona?</span></h4>';
							echo '<p>Realizá tu compra y avisanos por <strong>WhatsApp o Discord</strong>.<br>';
							echo 'En las próximas <strong>2 a 6 horas</strong> tendrás tus WCoins acreditadas.</p>';
						echo '</div>';

					echo '</div>';
				echo '</div>';
			echo '</div>';
		echo '</div>';
	echo '</div>';

	// Botones de pago
	echo '<div class="row">';

		// Mercado Pago
		echo '<div class="col-xs-6 col-sm-4">';
			echo '<a href="https://link.mercadopago.com.ar/mupga" target="_blank" class="thumbnail">';
				echo '<img src="'.__PATH_TEMPLATE_IMG__.'donation/mercadopago.png" alt="Mercado Pago">';
				echo '<div class="caption text-center"><strong>Pagar con Mercado Pago</strong></div>';
			echo '</a>';
		echo '</div>';

		// Binance Pay
		echo '<div class="col-xs-6 col-sm-4">';
			echo '<a href="#binanceModal" data-toggle="modal" class="thumbnail">';
				echo '<img src="'.__PATH_TEMPLATE_IMG__.'donation/binance.png" alt="Binance Pay">';
				echo '<div class="caption text-center"><strong>Pagar con Binance Pay</strong></div>';
			echo '</a>';
		echo '</div>';

	echo '</div>';

	// Modal Binance QR
	echo '
	<div class="modal fade" id="binanceModal" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-sm" role="document">
			<div class="modal-content" style="background:#1e2026; color:#fff; border-color:#f0b90b;">
				<div class="modal-header" style="border-color:#f0b90b;">
					<button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
					<h4 class="modal-title" style="color:#f0b90b;">&#9889; Binance Pay</h4>
				</div>
				<div class="modal-body text-center">
					<p>Escaneá el QR con tu app de Binance</p>
					<img src="'.__PATH_TEMPLATE_IMG__.'donation/binance-qr.png" style="max-width:100%; border-radius:8px;">
					<hr style="border-color:rgba(255,255,255,0.1);">
					<p class="text-muted" style="font-size:12px;">1 USDT = 1.000 WCoins<br>Una vez realizado el pago, avisanos por WhatsApp o Discord.</p>
				</div>
				<div class="modal-footer" style="border-color:#f0b90b;">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
				</div>
			</div>
		</div>
	</div>';
	
} catch(Exception $ex) {
	message('error', $ex->getMessage());
}