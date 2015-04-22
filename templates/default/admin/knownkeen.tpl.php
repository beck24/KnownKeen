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
			<p><label class="control-label" for="keen_project_id"><strong>Production Project ID</strong></label></p>
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
			<p><label class="control-label" for="keen_read_api_key"><strong>Production Read API Key</strong></label></p>
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
			<p><label class="control-label" for="keen_write_api_key"><strong>Production Write API Key</strong></label></p>
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
	<hr>
	<div class="row">
		<div class="span2">
			<p><label class="control-label" for="keen_project_id_test"><strong>Test Project ID</strong></label></p>
		</div>
		<div class="config-toggle span4">
			<input type="text"
				   name="keen_project_id_test"
				   value="<?= \Idno\Core\site()->config()->keen_project_id_test ?>">
		</div>
		<div class="span4">
			<p class="config-desc">The Project ID as assigned by the Keen.io dashboard</p>
		</div>
	</div>
	<div class="row">
		<div class="span2">
			<p><label class="control-label" for="keen_read_api_key_test"><strong>Test Read API Key</strong></label></p>
		</div>
		<div class="config-toggle span4">
			<input type="text"
				   name="keen_read_api_key_test"
				   value="<?= \Idno\Core\site()->config()->keen_read_api_key_test ?>">
		</div>
		<div class="span4">
			<p class="config-desc">The Read API key for your project as assigned by the keen.io dashboard</p>
		</div>
	</div>
	<div class="row">
		<div class="span2">
			<p><label class="control-label" for="keen_write_api_key_test"><strong>Test Write API Key</strong></label></p>
		</div>
		<div class="config-toggle span4">
			<input type="text"
				   name="keen_write_api_key_test"
				   value="<?= \Idno\Core\site()->config()->keen_write_api_key_test ?>">
		</div>
		<div class="span4">
			<p class="config-desc">The Write API key for your project as assigned by the keen.io dashboard</p>
		</div>
	</div>
	<hr>
	<div class="row">
		<div class="span2">
			<p><label class="control-label" for="keen_environment"><strong>Environment</strong></label></p>
		</div>
		<div class="config-toggle span4">
			<input type="checkbox" data-toggle="toggle" data-onstyle="info" data-on="Production" data-off="Test"
				   name="keen_environment"
				   value="true" <?php if (\Idno\Core\site()->config()->keen_environment == true) echo 'checked'; ?>>
		</div>
		<div class="span4">
			<p class="config-desc">Is this instance the production or test instance of your site? (will determine which API keys to use)</p>
		</div>
	</div>
	<hr>
	<div class="row">
		<div class="span10">
			<h2>Data Collection</h2>
		</div>
	</div>
	<div class="row">
		<div class="span2">
			<p><label class="control-label" for="keen_pageviews"><strong>Page Views</strong></label></p>
		</div>
		<div class="config-toggle span4">
			<input type="checkbox" data-toggle="toggle" data-onstyle="info" data-on="On" data-off="Off"
				   name="keen_pageviews"
				   value="true" <?php if (\Idno\Core\site()->config()->keen_pageviews == true) echo 'checked'; ?>>
		</div>
		<div class="span4">
			<p class="config-desc">Record page view analytics?</p>
		</div>
	</div>
	<hr>
	<?php
		$listener = new \IdnoPlugins\KnownKeen\Keen\KnownKeenIO();
		foreach ($listener->eventmap as $event => $method):
			$attr = 'keen_event_' . $event;
	?>
	<div class="row">
		<div class="span2">
			<p><label class="control-label" for="<?= $attr ?>"><strong><?= $event ?></strong></label></p>
		</div>
		<div class="config-toggle span4">
			<input type="checkbox" data-toggle="toggle" data-onstyle="info" data-on="On" data-off="Off"
				   name="<?= $attr ?>"
				   value="true" <?php if (\Idno\Core\site()->config()->$attr == true) echo 'checked'; ?>>
		</div>
		<div class="span4">
			<p class="config-desc"><?= $listener->eventDescriptions[$event] ?></p>
		</div>
	</div>
	
	<?php
		endforeach;
	?>
	
	<div class="row">
		<div class="span10">

			<button type="submit" class="btn btn-primary code">Save code</button>

		</div>
	</div>
	<?= \Idno\Core\site()->actions()->signForm('/admin/knownkeen/') ?>
</form>
</div>
