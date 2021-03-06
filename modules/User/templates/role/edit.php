
<ul id="submit-box-floating"></ul>

<h2><?= $this->pageTitle; ?></h2>

<form id="edit-form" action="" method="post">
	<?= FORMCODE; ?>	
	<input type="hidden" name="id" value="<?= $this->instanceId; ?>" />

	<div class="paragraph">
		<label class="title">Заголовок</label>
		<?= Html_Form::inputText(array('name' => 'title', 'value' => $this->title)); ?>
	</div>
	<div class="paragraph">
		<label class="title">Уровень</label>
		<?= Html_Form::inputText(array('name' => 'level', 'value' => $this->level, 'style' => 'width: 60px;')); ?>
		<span class="description">Число от 1 до 49</span>
	</div>
	<div class="paragraph">
		<label class="title">Описание</label>
		<?= Html_Form::textarea(array('name' => 'description', 'value' => $this->description, 'style' => 'width: 200px; height: 50px;')); ?>
	</div>
	<div class="paragraph">
		<label class="title">Флаги</label>
		<label>
			<?= Html_Form::radio(array('name' => 'flag', 'value' => '0', 'checked' => $this->flag == 0)); ?> Нет
		</label><br />
		<label>
			<?= Html_Form::radio(array('name' => 'flag', 'value' => User_RoleModel::FLAG_GUEST, 'checked' => $this->flag == User_RoleModel::FLAG_GUEST )); ?>
			Роль, присваиваемая гостю
		</label><br />
		<label>
			<?= Html_Form::radio(array('name' => 'flag', 'value' => User_RoleModel::FLAG_REG, 'checked' => $this->flag == User_RoleModel::FLAG_REG)); ?>
			Роль, присваиваемая зарегистрировавшемуся пользователю
		</label><br />
	</div>
	
	<? if(!$this->instanceId): ?>
	<div class="paragraph">
		<label class="title">Скопировать права доступа</label>
		<?= Html_Form::select(array('name' => 'copy_role'), array('0' => 'Не копировать') + $this->rolesList); ?>
	</div>
	<? endif; ?>

	<div class="paragraph" id="submit-box">
		<input id="submit-save" class="button" type="submit" name="action[admin/users/roles/save][admin/users/roles/list]" value="Сохранить" title="Созхранить изменения и вернуться к списку" />
		<a id="submit-cancel" class="button" href="<?= href('admin/users/roles/list'); ?>" title="Отменить все изменения и вернуться к списку">отмена</a>
		<? if($this->instanceId): ?>		
			<a id="submit-delete" class="button" href="<?= href('admin/users/roles/delete/'.$this->instanceId); ?>" title="Удалить запись">удалить</a>
		<? endif; ?>		
	</div>
</form>

<script type="text/javascript">

$(function(){
	// enableFloatingSubmits();
});

</script>
