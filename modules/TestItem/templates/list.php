
<?= $this->pagination; ?>

<? if($this->collection): ?>
	<? foreach($this->collection as $item): ?>	
	<p>
		<h3>id</h3>
		<?= $item['id']; ?>
		<h3>Группа</h3>
		<?= $item['group_id']; ?>
		<h3>Название</h3>
		<?= $item['name']; ?>
		<h3>Изображение</h3>
		<?= $item['img']; ?>
		<h3>Описание</h3>
		<?= $item['description']; ?>
		<h3>Дата создания</h3>
		<?= $item['date']; ?>
		<div><a href="<?= href('test-item/view/'.$item['id']); ?>">Подробней</a></div>
	</p>
	<? endforeach; ?>	
<? else: ?>
	<p>Сохраненных записей пока нет.</p>
<? endif; ?>
<?= $this->pagination; ?>
