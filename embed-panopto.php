<?php

/*
Plugin Name: Embed Panopto Aalto
Plugin URI: https://github.com/JonFabritius/embed-panopto-aalto
Description: WordPress Block for embedding Panopto videos and playlists, tweaked for Aalto University
Version: 1.0
Author: Jon Fabritius based 99.9% on Stephen Schrauger
Author URI: https://studios.aalto.fi
License: GPLv3
*/

/*
Embed Panopto is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Embed Panopto is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Embed Panopto. If not, see https://www.gnu.org/licenses/gpl-3.0.txt.
*/

add_action('init', array('PanoptoBlock','loadPanoptoBlock'));

class PanoptoBlock {
	/**
	 * register the react script for a block, and also define
	 * the server side render callback to allow for raw html.
	 */
	static function loadPanoptoBlock() {

		wp_register_script(
			'block-panopto',
			plugins_url('block-panopto.js', __FILE__),
			array('wp-blocks','wp-editor','wp-data')
		//filemtime(plugin_dir_path(__FILE__) . 'block-panopto.js')
		);

		register_block_type(
			'embed-panopto/block-panopto', array(
				                             'editor_script' => 'block-panopto',
				                             'render_callback' => array('PanoptoBlock','renderPanoptoCallback') // lets us do server-side rendering
			                             )
		);
	}

	/**
	 * Overwrites the client side 'save' method with our own data. This allows us to print out raw html without
	 * filtering it based on user permissions, so we can embed an iframe.
	 * @param $attributes // input elements from the client
	 * @param $content // post-filtered html from the client-side 'save' method. we don't use it here, instead we create our own html.
	 *
	 * @return string // like shortcode callbacks, this is the html that we render in place of the block.
	 */
	static function renderPanoptoCallback($attributes, $content){
		$u_url = $attributes['url']; // unsafe user input

		$url = parse_url($u_url);

		$viewer = "/panopto/pages/viewer.aspx";
		// if user pasted in a video with viewer.aspx, rewrite it with embed.aspx
		if (self::startsWith(strtolower($url['path']), $viewer)){
			$url['path'] = "/Panopto/pages/embed.aspx" + substr($url['path'], sizeof($viewer)); // combine embed plus the part of the user url after viewer.aspx
			// note: Panopto must be capitalized. otherwise, it redirects which messes up our check.
		}

		if (($url)
		    && (self::endsWith(strtolower($url['host']), "panopto.eu"))
		    && (self::startsWith(strtolower($url['path']), "/panopto/pages/embed.aspx"))){

			$s_url = esc_attr($u_url);
			return "
				<div class='panopto-iframe'>
					<iframe 
						src='{$s_url}'
						width='720'
			            height='405'
			            frameborder='0'
			            allowfullscreen='true'
			            allow='autoplay'
					></iframe>
				</div>
			";
		}
		return null;
	}

	// startsWith and endsWith code copied from https://stackoverflow.com/a/834355/306937
	static function startsWith($haystack, $needle)
	{
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}

	static function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}
}



// have to use init hook, since there is server-side rendering
// can't just use enqueue_block_editor_assets hook, since an editor-only return is client side rendered html, which is
// subject to html filtering (and would remove the iframe for less privileged users)
//add_action('enqueue_block_editor_assets', array('PanoptoBlock','loadPanoptoBlock'));
