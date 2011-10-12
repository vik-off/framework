
<ul id="submit-box-floating"></ul>

<h2><?= $this->pageTitle; ?></h2>

<?
/*
Html_Form::create('std-div')
	->method('post')
	->fields(array(
		'id' => array('type' => 'hidden'),
		'title' => array(
			'type' => 'text',
			'label' => 'Заголовок',
			'required' => true,
		    'attrs' => array('style' => 'width: 300px;')),
		'alias' => array(
			'type' => 'text',
			'label' => 'Псевдоним',
			'attrs' => array('style' => 'width: 300px;'),
		    'description' => '
				уникальный идентификатор страницы [a-z, 0-9].<br />
				Если не заполнен, система автоматически создаст псевдоним,<br />
				соответствующий id страницы.'),
		'body' => array(
			'type' => 'textarea',
			'label' => 'Текст',
			'wysiwyg' => true,
			'attrs' => array('style' => 'width: 98%; height: 400px;'),
		)
	))
	->values(array(
		'id' => $this->instanceId,
		'title' => $this->title,
		'body' => $this->body,
	))
	->render();
	*/
?>
<form id="edit-form" action="" method="post">
	<?= FORMCODE; ?>
	<input type="hidden" name="id" value="<?= $this->instanceId; ?>" />

	<p>
		<label class="title">Заголовок <span class="required">*</span></label>
		<input type="text" name="title" value="<?= $this->title; ?>" style="width: 300px;" />
	</p>
	
	<p>
		<label class="title">Псевдоним</label>
		<span class="description">
			уникальный идентификатор страницы [a-z, 0-9].<br />
			Если не заполнен, система автоматически создаст псевдоним,<br />
			соответствующий id страницы.
		</span>
		<input type="text" name="alias" value="<?= $this->alias; ?>" style="width: 300px;" />
	</p>
	
	<p>
		<label class="title">Текст</label>
		<textarea class="wysiwyg" style="width: 98%; height: 400px;" name="body"><?= $this->body; ?></textarea>
	</p>
	
	<p>
		<label class="title-inline">Тип:</label>
		<?= HtmlForm::select(
			array('name' => 'type'),
			array('html', 'php'),
			$this->type,
			array('keyEqVal')); ?>
	</p>
	
	<p>
		<label class="title">meta description</label>
		<textarea style="width: 300px; height: 60px;" name="meta_description"><?= $this->meta_description; ?></textarea>
	</p>
	
	<p>
		<label class="title">meta keywords</label>
		<textarea style="width: 300px; height: 60px;" name="meta_keywords"><?= $this->meta_keywords; ?></textarea>
	</p>
	
	<p>
		<label class="title">
			<input type="checkbox" name="published" value="1" <? if($this->published): ?>checked="checked"<? endif; ?> />
			Опубликовать
		</label>
	</p>
	
	<div class="paragraph" id="submit-box">
		<input id="submit-save" class="button" type="submit" name="action[admin/page/<? if($this->instanceId): ?>save<? else: ?>create<? endif; ?>][admin/content/page/list]" value="Сохранить" title="Созхранить изменения и вернуться к списку" />
		
		<? if($this->instanceId): ?>
			<input id="submit-apply" class="button" type="submit" name="action[admin/page/save]" value="Применить" title="Сохранить изменения и продолжить редактирование" />
		<? endif; ?>
		
		<a id="submit-cancel" class="button" href="<?= href('admin/content/page/list'); ?>" title="Отменить все изменения и вернуться к списку">отмена</a>
		
		<? if($this->instanceId): ?>
			<a id="submit-delete" class="button" href="<?= href('admin/content/page/delete/'.$this->instanceId); ?>" title="Удалить запись">удалить</a>
		<? endif; ?>
		
		<? if($this->instanceId): ?>
			<a id="submit-copy" class="button" href="<?= href('admin/content/page/copy/'.$this->instanceId); ?>" title="Сделать копию записи">копировать</a>
		<? endif; ?>
		
	</div>
</form>

<script type="text/javascript" src="libs/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">

$(function(){
	
	$("#edit-form").validate({<?= $this->validation; ?>});
	
	tinyMCE.init($.extend(getDefaultTinyMceSettings('<?= WWW_ROOT; ?>'), {
		mode : 'specific_textareas',
		editor_selector : 'wysiwyg'
	}));
	
	enableFloatingSubmits();
});

</script>