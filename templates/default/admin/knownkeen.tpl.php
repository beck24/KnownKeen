<div class="row">

    <div class="span10 offset1">
		<?= $this->draw('admin/menu') ?>
        <h1>Keen.io</h1>

        <div class="explanation clearfix">
            <p>
				Web and event analytics with keen.io
            </p>
        </div>
    </div>

</div>
<div class="span10 offset1">
<form action="/admin/knownkeen/" class="form-horizontal" method="post">
	<div class="row">
		<div class="span10">
			<h2>Keen.io Settings</h2>
		</div>
	</div>
	<div class="row">
		<div class="span2">
			<p><label class="control-label" for="keen_project_id"><strong>Project ID</strong></label></p>
		</div>
		<div class="config-toggle span4">
			<input type="text"
				   name="keen_project_id"
				   value="<?= \Idno\Core\site()->config()->keen_project_id ?>">
		</div>
		<div class="span4">
			<p class="config-desc">The Project ID as assigned by the Keen.io dashboard</p>
		</div>
	</div>
	<div class="row">
		<div class="span2">
			<p><label class="control-label" for="keen_read_api_key"><strong>Read API Key</strong></label></p>
		</div>
		<div class="config-toggle span4">
			<input type="text"
				   name="keen_read_api_key"
				   value="<?= \Idno\Core\site()->config()->keen_read_api_key ?>">
		</div>
		<div class="span4">
			<p class="config-desc">The Read API key for your project as assigned by the keen.io dashboard</p>
		</div>
	</div>
	<div class="row">
		<div class="span2">
			<p><label class="control-label" for="keen_write_api_key"><strong>Write API Key</strong></label></p>
		</div>
		<div class="config-toggle span4">
			<input type="text"
				   name="keen_write_api_key"
				   value="<?= \Idno\Core\site()->config()->keen_write_api_key ?>">
		</div>
		<div class="span4">
			<p class="config-desc">The Write API key for your project as assigned by the keen.io dashboard</p>
		</div>
	</div>
	
	<div class="row">
		<div class="span10">

			<button type="submit" class="btn btn-primary code">Save code</button>

		</div>
	</div>
	<?= \Idno\Core\site()->actions()->signForm('/admin/knownkeen/') ?>
</form>
</div>