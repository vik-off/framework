<p>
	<div style="float: right;">
		<a class="button" href="<?= $this->href('admin/manage/user-statistics/delete'); ?>">Очистить статистику</a>
	</div>
	<div class="clear"> </div>
</p>
<style type="text/css">
table.statistics-nested{
	margin: 0;
}
table.statistics-nested td{
	border: none !important;
}
table.statistics{
	border: double 3px #555;
}
table.statistics th{
	background-color: #555;
	color: #FFF;
	border: solid 1px #EEE;
}
table.statistics .urls td{
	text-align: left;
	padding: 2px 5px;
	white-space: nowrap;
}
table.statistics>tbody:hover{
	background-color: #FFFFE2;
}

table.statistics>tbody:nth-child(even){
	background-color: #EBF0FF;
}
table.statistics>tbody:nth-child(odd){
	background-color: #FFF;
}
table.statistics>tbody:nth-child(even) td{
	border: none;
}
table.statistics>tbody:nth-child(odd) td{
	border: none;
}

table.statistics>tbody:nth-child(even) tr.info{
	border-bottom: dashed 1px #FFF;
}
table.statistics>tbody:nth-child(odd) tr.info{
	border-bottom: dashed 1px #EBF0FF;
}

table.statistics>tbody:nth-child(even) td.detail{
	border-left: dashed 1px #FFF;
}
table.statistics>tbody:nth-child(odd) td.detail{
	border-left: dashed 1px #EBF0FF;
}

table.statistics>tbody:nth-child(even) tr.urls{
	/* background-color: #e8f4fb; */
}
table.statistics>tbody:nth-child(odd) tr.urls{
	/* background-color: #FAFAFA; */
}
</style>

<form method="get" action="" class="paragraph">
	
	<? if (!empty($_GET['sort'])): ?>
		<input type="hidden" name="sort" value="<?= $_GET['sort'] ?>" />
	<? endif; ?>
	
	<table class="small-grid" style="margin: 5px 0 1em; text-align: center;">
		<legend style="font-weight: bold;">Фильтр</legend>
		<tr>
			<td>Пользователи:</td>
			<td>IP адреса:</td>
			<td>Браузеры:</td>
			<td rowspan="2">
				<input class="button-small" type="submit" value="Применить"><br /><br />
				<a class="button-small" href="<?= $this->href('admin/manage/user-statistics'); ?>">Сбросить</a>
			</td>
		</tr>
		<tr>
			<td>
				<?= Html_Form::select(array('name' => 'users[]', 'multiple' => 'multiple', 'size' => 6),
					array('0' => 'Все', '-1' => 'Гости') + $this->filters['users'],
					getVar($_GET['users'])); ?>
			</td>
			<td>
				<?= Html_Form::select(array('name' => 'ips[]', 'multiple' => 'multiple', 'size' => 6),
					array('0' => 'Все') + $this->filters['ips'],
					getVar($_GET['ips'])); ?>
			</td>
			<td>
				<?= Html_Form::select(array('name' => 'browsers[]', 'multiple' => 'multiple', 'size' => 6),
					array('0' => 'Все', '-1' => 'Не определенные') + $this->filters['browsers'],
					getVar($_GET['browsers'])); ?>
			</td>
		</tr>
	</table>
</form>

<? if ($this->collection): ?>
	
	<?= $this->pagination; ?>
	
	<table class="grid statistics" style="text-align: center;">
	<thead>
	<tr>
		<th><?= $this->sorters['last_date']; ?></th>
		<th><?= $this->sorters['num_pages']; ?></th>
		<th><?= $this->sorters['login']; ?></th>
		<th><?= $this->sorters['user_ip']; ?></th>
		<th><?= $this->sorters['browser']; ?></th>
		<th><?= $this->sorters['screen_resolution']; ?></th>
		<th>Опции</th>
	</tr>
	</thead>
	
	<? foreach ($this->collection as $item): ?>
	<tbody>
	<tr class="info" style="">
		<td></td>
		<td style="text-align: right;">
			<? if ($item['pages_info']): ?>
				всего: <?= $item['num_pages']; ?>
			<? else: ?>
				-
			<? endif; ?>
		</td>
		<td style="white-space: nowrap;"><a href="<?= href('admin/users/view/'.$item['uid']); ?>"><?= $item['login']; ?></a></td>
		<td style="text-align: left;" class="grey"><?= $item['user_ip']; ?></td>
		<? if ($item['has_js']): ?>
			<td><?= $item['has_js'] ? $item['browser_name'].'&nbsp;'.$item['browser_version'] : '-'; ?></td>
			<td><?= $item['has_js'] ? $item['screen_width'].'x'.$item['screen_height'] : '-'; ?></td>
		<? else: ?>
			<td colspan="2" class="grey"><?= $item['user_agent_raw']; ?></td>
		<? endif; ?>
		<td rowspan="4" class="detail">
			<a href="<?= href('admin/manage/user-statistics/view/'.$item['id']); ?>">Подробней</a>
		</td>
	</tr>
	<? if ($item['referer']): ?>
	<tr class="urls" style="">
		<td></td>
		<td class="grey" style="text-align: right;">Referer</td>
		<td colspan="4" class="grey"><?= $item['referer']; ?></td>
	</tr>
	<? endif; ?>
	<tr class="urls" style="">
		<td class="grey"><?= $item['pages_info']['first_page_time']; ?></td>
		<td class="grey" style="text-align: right;">Первая</td>
		<td colspan="4">
			<? if (strlen($item['pages_info']['first_page']) > 80): ?>
				<?= implode('<wbr>', str_split($item['pages_info']['first_page'], 80)); ?>
			<? else: ?>
				<?= $item['pages_info']['first_page']; ?>
			<? endif; ?>
		</td>
	</tr>
	<tr class="urls" style="">
		<td><?= $item['pages_info']['last_page_time']; ?></td>
		<td class="grey" style="text-align: right;">Последняя</td>
		<td colspan="4">
			<? if (strlen($item['pages_info']['last_page']) > 80): ?>
				<?= implode('<wbr>', str_split($item['pages_info']['last_page'], 80)); ?>
			<? else: ?>
				<?= $item['pages_info']['last_page']; ?>
			<? endif; ?>
		</td>
	</tr>
	</tbody>
	<? endforeach; ?>
	</table>
<? else: ?>
	<p>Сохраненных записей пока нет.</p>
<? endif; ?>

<?= $this->pagination; ?>

