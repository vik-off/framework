
<div class="options-row">
	<a href="<?= href('admin/users/create'); ?>">Добавить нового пользователя</a>
</div>

<?= $this->pagination; ?>

<? if($this->collection): ?>
	<table class="grid wide tr-highlight" style="text-align: center;">
	<tr>
		<th><?= $this->sorters['id']; ?></th>
		<th><?= $this->sorters['login']; ?></th>
		<th><?= $this->sorters['fio']; ?></th>
		<th><?= $this->sorters['role_str']; ?></th>
		<th><?= $this->sorters['regdate']; ?></th>
		<th>Опции</th>
	</tr>
	<? foreach($this->collection as $item): ?>	
	<tr>
		<td><?= $item['id']; ?></td>
		<td><?= $item['login']; ?></td>
		<td><?= $item['fio']; ?></td>
		<td><?= $item['role_str']; ?></td>
		<td><?= $item['regdate']; ?></td>
			
		<td class="center" style="width: 120px;">
			<div class="tr-hover-visible options">
				<a href="<?= href('admin/users/view/'.$item['id']); ?>" title="Просмотреть"><img src="images/backend/icon-view.png" alt="Просмотреть" /></a>
				<a href="<?= href('admin/users/edit/'.$item['id']); ?>" title="Редактировать"><img src="images/backend/icon-edit.png" alt="Редактировать" /></a>
				<a href="<?= href('admin/users/ban/'.$item['id']); ?>" title="Блокировать"><img src="images/backend/icon-ban.png" alt="Блокировать" /></a>
				<a href="<?= href('admin/users/delete/'.$item['id']); ?>" title="Удалить"><img src="images/backend/icon-delete.png" alt="Удалить" /></a>
			</div>
		</td>
	</tr>
	<? endforeach; ?>	
	</table>
<? else: ?>
	<p>Сохраненных записей пока нет.</p>
<? endif; ?>

<?= $this->pagination; ?>