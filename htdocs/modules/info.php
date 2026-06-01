<?php
/**
 * WebEngine CMS
 * https://webenginecms.org/
 * 
 * @version 1.2.1
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2020 Lautaro Angelico, All Rights Reserved
 * 
 * Licensed under the MIT license
 * http://opensource.org/licenses/MIT
 */

// Module Title
echo '<div class="page-title"><span>'.lang('module_titles_txt_17').'</span></div>';

?>

<!-- SERVER STATISTICS -->
<table class="table table-condensed table-hover table-striped table-bordered">
	<thead>
		<tr>
			<th colspan="2">General Information</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td style="width:50%;">Server Version</td>
			<td style="width:50%;"><?php echo config('server_info_season'); ?></td>
		</tr>
		<tr>
			<td style="width:50%;">Experience</td>
			<td style="width:50%;"><?php echo config('server_info_exp'); ?></td>
		</tr>
		<tr>
			<td style="width:50%;">Master Experience</td>
			<td style="width:50%;"><?php echo config('server_info_masterexp'); ?></td>
		</tr>
		<tr>
			<td style="width:50%;">Drop</td>
			<td style="width:50%;"><?php echo config('server_info_drop'); ?></td>
		</tr>
	</tbody>
</table>

<br />

<!-- CHAOS MACHINE RATES -->
<h2>Chaos Machine</h2>
<table class="table table-condensed table-hover table-striped table-bordered">
	<tbody>
		<tr>
			<td width="40%" rowspan="2" style="vertical-align: middle;">Combination</td>
			<td width="60%" colspan="2" class="text-center">
				Maximum Success Rate
			</td>
		</tr>
		<tr>
			<td width="30%" class="text-center">Normal</td>
			<td width="30%" class="text-center">? VIP</td>
		</tr>
		<tr>
			<td scope="row">Items +10, +11</td>
			<td class="text-center">60% + Luck</td>
			<td class="text-center">65% + Luck</td>
		</tr>
		<tr>
			<td scope="row">Items +12, +13</td>
			<td class="text-center">50% + Luck</td>
			<td class="text-center">55% + Luck</td>
		</tr>
		<tr>
			<td scope="row">Items +14, +15</td>
			<td class="text-center">40% + Luck</td>
			<td class="text-center">45% + Luck</td>
		</tr>
		<tr>
			<td scope="row">Arma Chaos</td>
			<td class="text-center">65%</td>
			<td class="text-center">75%</td>
		</tr>
		<tr>
			<td scope="row">Wings Level 1</td>
			<td class="text-center">65%</td>
			<td class="text-center">75%</td>
		</tr>
		<tr>
			<td scope="row">Wings Level 2</td>
			<td class="text-center">60%</td>
			<td class="text-center">70%</td>
		</tr>
		<tr>
			<td scope="row">Wings Level 3</td>
			<td class="text-center">40%</td>
			<td class="text-center">50%</td>
		</tr>
		<tr>
			<td scope="row">Cape of Lord Mix</td>
			<td class="text-center">60%</td>
			<td class="text-center">70%</td>
		</tr>
		<tr>
			<td scope="row">Feather of Condor</td>
			<td class="text-center">50%</td>
			<td class="text-center">60%</td>
		</tr>
		<tr>
			<td scope="row">Fragment of Horn Mix</td>
			<td class="text-center">x%</td>
			<td class="text-center">x%</td>
		</tr>
		<tr>
			<td scope="row">Broken Horn Mix</td>
			<td class="text-center">x%</td>
			<td class="text-center">x%</td>
		</tr>
		<tr>
			<td scope="row">Horn of Fenrir Mix</td>
			<td class="text-center">x%</td>
			<td class="text-center">x%</td>
		</tr>
		<tr>
			<td scope="row">Ancient Hero's Soul</td>
			<td class="text-center">x%</td>
			<td class="text-center">x%</td>
		</tr>
		<tr>
			<td scope="row">Socket Weapon Mix</td>
			<td class="text-center">x%</td>
			<td class="text-center">x%</td>
		</tr>
	</tbody>
</table>

<br />

<!-- PARTY EXPERIENCE BONUS -->
<h2>Party Bonus Experience</h2>
<table class="table table-condensed table-hover table-striped table-bordered">
	<thead>
		<tr>
			<th>Members</th>
			<th class="text-center">Experience Rate</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>2 Players</td>
			<td class="text-center">EXP% + 5%</td>
		</tr>
		<tr>
			<td>3 Players</td>
			<td class="text-center">EXP% + 5%</td>
		</tr>
		<tr>
			<td>4 Players</td>
			<td class="text-center">EXP% + 7%</td>
		</tr>
		<tr>
			<td>5 Players</td>
			<td class="text-center">EXP% + 10%</td>
		</tr>
	</tbody>
</table>

<br />

<!-- COMMANDS -->
<h2>Commands</h2>
<table class="table table-condensed table-hover table-striped table-bordered">
	<thead>
		<tr>
			<th>Comando</th>
			<th>Descripción</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>/f</td>
			<td>Agrega puntos de fuerza.</td>
		</tr>
		<tr>
			<td>/a</td>
			<td>Agrega puntos de agilidad.</td>
		</tr>
		<tr>
			<td>/v</td>
			<td>Agrega puntos de vitalidad.</td>
		</tr>
		<tr>
			<td>/e</td>
			<td>Agrega puntos de energía.</td>
		</tr>
		<tr>
			<td>/c</td>
			<td>Agrega puntos de comando.</td>
		</tr>
		<tr>
			<td>/readd</td>
			<td>Reiniciar los puntos.</td>
		</tr>
	</tbody>
</table>

<br />

<!-- INVASIONES -->
<h2>Invasiones</h2>
<table class="table table-condensed table-hover table-striped table-bordered">
	<thead>
		<tr>
			<th>Invasión</th>
			<th>Recompensa</th>
			<th class="text-center">Cantidad</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Skeleton</td>
			<td>—</td>
			<td class="text-center">x2</td>
		</tr>
		<tr>
			<td>Red Dragón</td>
			<td>—</td>
			<td class="text-center">x4</td>
		</tr>
		<tr>
			<td>White Wizard</td>
			<td>—</td>
			<td class="text-center">x3</td>
		</tr>
		<tr>
			<td>New Year</td>
			<td>—</td>
			<td class="text-center">x10</td>
		</tr>
		<tr>
			<td>Rabbit</td>
			<td>—</td>
			<td class="text-center">x10</td>
		</tr>
		<tr>
			<td>Summer</td>
			<td>—</td>
			<td class="text-center">x10</td>
		</tr>
		<tr>
			<td>Cursed Santa</td>
			<td>—</td>
			<td class="text-center">x2</td>
		</tr>
	</tbody>
</table>

<br />

<!-- EVENTOS -->
<h2>Eventos</h2>
<table class="table table-condensed table-hover table-striped table-bordered">
	<thead>
		<tr>
			<th>Evento</th>
			<th>Recompensa</th>
			<th class="text-center">Cantidad</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Blood Castle</td>
			<td>—</td>
			<td class="text-center">—</td>
		</tr>
		<tr>
			<td>Devil Square</td>
			<td>—</td>
			<td class="text-center">—</td>
		</tr>
		<tr>
			<td>Chaos Castle</td>
			<td>—</td>
			<td class="text-center">—</td>
		</tr>
	</tbody>
</table>