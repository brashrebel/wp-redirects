<?php
/*
Plugin Name: Redirects
Description: Create redirects from old urls to the new.
Version: 1.0
Author: Loud Dog
Author URI: http://www.louddog.com
*/

// TODO: Delete options on deactivation

new LoudDog_Redirects;
class LoudDog_Redirects {
	var $slug = 'louddog_redirects';
	
	function __construct() {
		add_action('init', array($this,'redirect'), 1);
		add_action('admin_menu', array($this,'admin_menu'));
		add_action('admin_init', array($this,'save'));
	}

	function admin_menu() {
		add_options_page(
			'Redirects',
			'Redirects',
			10,
			$this->slug,
			array($this, 'options')
		);
	}

	function options() {
		$redirects = get_option($this->slug);
		if (is_array($redirects)) ksort($redirects);
		else $redirects = array();
		
		?>
	
		<div class="wrap">
			<h2>Redirects</h2>
			
			<p>
				Looks like you've got
				<strong><?php echo count($redirects) ?></strong>
				<?php echo count($redirects) == 1 ? "redirect" : "redirects" ?>.
			</p>
			
			<h3>New</h3>
			<form method="post" action="options-general.php?page=<?php echo $this->slug ?>" enctype="multipart/form-data">
				<table>
					<tr>
						<th>From</th>
						<th>To</th>
					</tr>
					<tr>
						<td><input type="text" name="<?php echo $this->slug ?>[from][new]" style="width:30em" />&nbsp;&raquo;&nbsp;</td>
						<td><input type="text" name="<?php echo $this->slug ?>[to][new]" style="width:30em;" /></td>
					</tr>
					<tr>
						<td><small>example: /about.htm</small></td>
						<td><small>example: <?php echo get_option('home'); ?>/about/</small></td>
					</tr>
				</table>

				<p class="submit"><input type="submit" name="<?php echo $this->slug ?>_submit" class="button-primary" value="<?php _e('Save') ?>" /></p>
			</form>

			<h3>Existing</h3>
			<form method="post" action="options-general.php?page=<?php echo $this->slug ?>" enctype="multipart/form-data">
				<table>
					<tr>
						<th>From</th>
						<th>To</th>
					</tr>

					<?php if (!empty($redirects)) foreach ($redirects as $from => $to) { ?>

						<tr>
							<td><input type="text" name="<?php echo $this->slug ?>[from][]" value="<?php echo $from ?>" style="width:30em" />&nbsp;&raquo;&nbsp;</td>
							<td><input type="text" name="<?php echo $this->slug ?>[to][]" value="<?php echo $to ?>" style="width:30em;" /></td>
							<td><a href="options-general.php?page=<?php echo $this->slug ?>&<?php echo $this->slug ?>[delete]=<?php echo urlencode($from) ?>">delete</a></td>
						</tr>

					<?php } ?>
				</table>
				
				<p>Or upload a .csv file full of 'em: <input type="file" name="<?php echo $this->slug ?>_csv" /></p>

				<p class="submit"><input type="submit" name="<?php echo $this->slug ?>_submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
			</form>
		</div>

		<?php
	}

	function save() {
		if (!isset($_REQUEST[$this->slug]) && empty($_FILES[$this->slug.'_csv'])) return;

		$data = $_REQUEST[$this->slug];
		$redirects = get_option($this->slug);

		if (!empty($_FILES[$this->slug.'_csv']['tmp_name'])) {
			$csv = explode("\n", file_get_contents($_FILES[$this->slug.'_csv']['tmp_name']));
			foreach ($csv as $redirect) {
				list($from, $to) = explode(',', $redirect);
				if (empty($from) || empty($to)) continue;
				$from = trim($from);
				$to = trim($to);
				$redirects[$from] = $to;
			}
		} else if (isset($data['delete'])) {
			unset($redirects[$data['delete']]);
		} else if (isset($data['from']['new'])) {
			$from = trim($data['from']['new']);
			$to = trim($data['to']['new']);
			$redirects[$from] = $to;
		} else {
			$redirects = array();
			$changes = array_combine($data['from'], $data['to']);
			foreach ($changes as $from => $to) {
				$from = trim($from);
				$to = trim($to);
				$redirects[$from] = $to;
			}
		}

		$processed = array();
		foreach ($redirects as $from => $to) {
			if (!preg_match("/^\//", $from)) {
				$from = "/$from";
			}

			if (!preg_match("/^https?:\/\/|\//", $to)) {
				$to = preg_match("/\.(com|net|org)/", $to)
					? "http://$to"
					: "/$to";
			}

			$processed[$from] = $to;
		}
		$redirects = $processed;

		update_option($this->slug, $redirects);

		wp_redirect("options-general.php?page=$this->slug");
		exit;
	}

	function redirect() {
		$redirects = get_option($this->slug);
		if (is_array($redirects)) {
			extract(parse_url($_SERVER['REQUEST_URI']));

			if (isset($redirects[$path])) {
				$to = $redirects[$path];
				if (!empty($query)) $to .= "?$query";
				wp_redirect($to, 301);
				exit;
			}
		}
	}
}