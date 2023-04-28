<?php

/** Defines a Threesenter PHP helper. */
class Threesenter {

	/** A list of Threesenter instances */
	static $instances = [];

	/** Initializes a new instance of Threesenter.
	 *  @param data The initialization data. */
	function __construct($data) {

		// Check the initialization data
		if($data) {

			// Check if the data is a URL
			if(is_string($data)) {
				try {
					$url = $data;
					if (!is_readable($url)) throw new Exception('Invalid URL');
					$file = fopen($url, 'r');
					$data = json_decode(fread($file, filesize($data)));
					if (is_null($data)) throw new Exception('Invalid JSON');
					fclose($file);
					$data->url = $url;
				}
				catch (Exception $e) {
					die ('Unable to open: ' . $url . ' ('.$e->getMessage().')');
				}
			}
		}

		// Unless explicitly stated otherwise, parse the parameters
		if (!isset($data->parameters) || !$data->parameters) {
			
			// Get the version type
			if (isset($_GET['v'])) $data->version = $_GET['v'];
			if (isset($_POST['v'])) $data->version = $_POST['v'];
	
			// Get the context type
			if (isset($_GET['c'])) $data->context = $_GET['c'];
			if (isset($_POST['c'])) $data->context = $_POST['c'];
		}

		// Validate the values
		$this->version = 'accessible';
		if (isset($data->version)) switch ($data->version) {
			case 'accessible': $this->version = 'accessible'; break;
			case 'classic': $this->version = 'classic'; break;
			case 'webxr': $this->version = 'webxr'; break;
			default: $this->version = 'accessible'; break;
		}

		// Get the context
		$this->context = (isset($data->context))? $data->context : '';

		// Save the data
		$this->data = $data;

		// SAve the path
		$this->path = dirname($url);

		// The dCSS data in portrait mode
		$this->portrait = '';

		// Create the document
		$this->createDocument();

		// Add the instance to the list
		array_push(self::$instances, $this);
	}


	/** Initializes a new instance of the Threesenter.
	 * @param data The initialization data. */
	static function init($data) { return new Threesenter($data); }


	/** Calculates the relative path from one URL to another.
	 * @param from The origin URL.
	 * @param to The destination URL. */
	private function getRelativePath($from, $to) {
		$dir = explode(DIRECTORY_SEPARATOR, is_file($from) ? 
			dirname($from) : rtrim($from, DIRECTORY_SEPARATOR));
		$file = explode(DIRECTORY_SEPARATOR, $to);
		while ($dir && $file && ($dir[0] == $file[0])) {
			array_shift($dir); array_shift($file);
		}
		return str_repeat('..'. DIRECTORY_SEPARATOR, 
			count($dir)) . implode(DIRECTORY_SEPARATOR, $file);
	}


	/** Creates a HTML element.
	 * @param tag The tag of the element.
	 * @param parent The parent element.
	 * @param attribs The attributes of the element.
	 * @param text The inner text of the element.
	 * @return The generated HTML element. */
	private function createElement($tag, $parent = null, $attribs = null, 
		$text = null) {
		$elem = $this->dom->createElement($tag, $text);
		if ($attribs) foreach ($attribs as $key => $value) {
			$attrib = $this->dom->createAttribute($key);
			$attrib->value=(isset($value))?$value : '';
			$elem->appendChild($attrib);
		}
		if ($parent == null) $parent = $this->dom;
		$parent->appendChild($elem);
		return $elem;
	}

	/** Creates CSS code.
	 * @param text The inner text of the element.
	 * @return The generated HTML element. */
	private function createCssCode($item) {

		// Create the CSS code (or load it)
		$css = (isset($item->css))? $item->css : '';

		// Basic properties
		if(isset($item->width)) $css .= "width: $item->width; ";
		if(isset($item->height)) $css .= "height: $item->height; ";
		if(isset($item->top)) $css .= "top: $item->top; ";
		if(isset($item->font)) $css .= "font-family: $item->font; ";
		if(isset($item->font_size)) $css .= "font-size: $item->font_size; ";
		if(isset($item->font_weight)) $css .= "font-weight: $item->font_weight; ";
		if(isset($item->color)) $css .= "color: $item->color; ";
		if(isset($item->background)) $css .= "background: $item->background; ";
		if(isset($item->line_height)) $css .= "line-height: $item->line_height; ";
		if(isset($item->padding)) $css .= "padding: $item->padding; ";
		
		// Distribute child elements
		if (isset($item->distribution)) {
			$css .= 'display: flex; ';
			switch ($item->distribution) {
				case 'vertical': $css .= 'flex-direction: column; ';break;
				case 'horizontal': $css .= 'flex-direction: row; ';break;
				case 'vertical separated': $css .= 'flex-direction: column; justify-content: space-between; ';break;
				case 'horizontal separated': $css .= 'flex-direction: row; justify-content: space-between; ';break;
				case 'vertical grouped': $css .= 'flex-direction: column; justify-content: space-around; ';break;
				case 'horizontal grouped': $css .= 'flex-direction: row; justify-content: space-around; ';break;
			}	
		}
		
		// Check the type of align
		if (isset($item->align)) {
			$css .= 'display: flex; ';
			$css .= 'flex-direction: column; ';
			$vertical = 'top';
			switch ($item->align) {
				case "top_left": $css .= 'text-align: left; '; break;
				case "top_center": $css .= 'text-align: center; '; break;
				case "top_right": $css .= 'text-align: right; '; break;
				case "middle_left": $css .= 'text-align: left; '; $vertical = 'middle'; break;
				case "middle_center": $css .= 'text-align: center; '; $vertical = 'middle'; break;
				case "middle_right": $css .= 'text-align: right; '; $vertical = 'middle'; break;
				case "bottom_left": $css .= 'text-align: left; '; $vertical = 'bottom'; break;
				case "bottom_center": $css .= 'text-align: center; '; $vertical = 'bottom'; break;
				case "bottom_right": $css .= 'text-align: right; '; $vertical = 'bottom'; break;
			}
			
			switch ($vertical) {
				case "top": $css .= 'justify-content: start; '; break;
				case "middle": $css .= 'justify-content: center; '; break;
				case "bottom": $css .= 'justify-content: end; '; break;
			}
		}

		// Disable the items that don't have the right context
		if (isset($item->context)) {
			if($item->context != $this->context) $css .= 'display: none; ';
		}

		// Set the Z-index (depth) value
		if (isset($item->z)) $css .= "z-index: ".intval(10000 / $item->z)."; ";

		// Disable the items that don't have the right context
		if (isset($item->portrait)) {
			$portrait = '';
			switch ($item->portrait) {
				case "hidden": $portrait .= 'display: none; '; break;
				case "rotate": 	$portrait .= 'flex-direction: ' .
					((strpos($css, 'column') > 0)? 'row; ' : 'column; ');
				break;
			}
			$this->portrait .= "\t#$item->id { $portrait}\n";
		}

		// Return the css code
		return $css;
	}


	/** Creates the element of an item.
	 * @param item The item data.
	 * @param parentElement The parent element.
	 * @param nodePath The node path.
	 * @return The generated item element. */
	private function createItemElement($item, $parentElement, $nodePath = '/') {
		
		// Get or create the name
		if (!isset($item->id)) $item->id = $parentElement->getAttribute("id") . 
			sizeof($parentElement->childNodes);
		$tag = 'div'; $attribs = ['id'=>$item->id]; $text = '';
		$css = $this->createCssCode($item);

		// Check the type of element
		if(isset($item->type)) switch ($item->type) {
			case 'image': 
				$tag = 'img';
				if (isset($item->image)) $attribs += ['src' => $item->image];
				break;
		}

		// Add the text
		if (isset($item->text)) $text = $item->text;

		// Distribute elements
		if (isset($item->style)) $attribs += ["class" => $item->style];
		
		// Add the css code
		if (strlen($css) > 0) $this->css .= "#$item->id { $css}\n";
		
		// Create the HTML element
		if(isset($item->link)) 
			$parentElement = $this->createElement('a', 
			$parentElement, ['href'=> $item->link]);
		$elem = $this->createElement($tag, $parentElement, $attribs, $text);

		// Create the subitems
		if (isset($item->items)) foreach ($item->items as $subitem) 
			$this->createItemElement($subitem, $elem);
	}

	
	/** Creates the HTML document. */
	private function createDocument() {

		// Generate the Document Object model
		$this->dom = new DOMDocument('1.0', 'utf-8');
		$this->dom->preserveWhiteSpace = false; $this->dom->formatOutput = true;

		// Generate the basic HTML elements
		$html = $this->createElement('html');
		$head = $this->createElement('head', $html);
		$this->createElement('meta', $head, ['charset'=>'UTF-8'], '');
		if (isset($this->data->title)) $this->createElement('title', $head, 
			null, $this->data->title);
		if (isset($this->data->description)) $this->createElement('meta', $head,
			['name' => 'description', 'content' => $this->data->description]);
		if (isset($this->data->keywords)) $this->createElement('meta', $head, 
			['name' => 'keywords', 'content' => $this->data->keywords]);
		if (isset($this->data->author)) $this->createElement('meta', $head, 
			['name' => 'author', 'content' => $this->data->author]);
		$this->createElement('meta', $head, ['name' => 'viewport', 'content' => 
			'width=device-width, user-scalable=no, initial-scale=1.0,' . 
			'maximum-scale=1.0, minimum-scale=1.0']);
		$body = $this->createElement('body', $html);


		// Create the main element
		$attribs = ['id' => $this->data->id, 'class' => 'Threesenter'];
		$main = $this->createElement('div', $body, $attribs);
		
		try {
			// Process the data
			$data = $this->data;

			// Create the global styles
			$this->css = '';
			if (isset($data->styles)) foreach ($data->styles as $style)
				$this->css .= ".$style->id { ".$this->createCssCode($style). "; }\n";

			// Create the layers
			if (!isset($data->layers)) throw new Exception('No layer defined');
			$layerCount = sizeof($data->layers);
			for ($layerIndex = 0; $layerIndex < $layerCount; $layerIndex++) {
				$layer = $data->layers[$layerIndex];

				// Get or create the name
				if (!isset($layer->id)) $layer->id = "layer" . $layerIndex;
				$attribs = ['id' => $layer->id, 'class' => 'layer'];

				// Create the css code
				$css = $this->createCssCode($layer);
				if (!isset($layer->type)) $layer->type = 'fixed';
				switch ($layer->type) {
					case 'vertical': $css .= 'overflow: auto; '; break;
					case 'vertical snap': $css .= 'overflow: auto; scroll-snap-type: y mandatory;'; break;
				}
			

				// Check if the layer has to capture events
				if (isset($layer->captureEvents))
					$css .= 'pointer-events: ' . 
						(($layer->captureEvents)? 'all' : 'none') . ' ; ';

				// Add the css code
				if (strlen($css) > 0) $this->css .= "#$layer->id { $css}\n";
				
				// Create the HTML element
				$elem = $this->createElement('div', $main, $attribs);

				// Create the items
				if (!isset($layer->items)) $layer->items = [];
				foreach ($layer->items as $item)
					$this->createItemElement($item, $elem);
			}
		}
		catch(Exception $e) { 
			die ('Unable to parse data HTML: ' . $e->getMessage());
		}

		// Add the portrait rules to the css
		if (strlen($this->portrait) > 0) {
			$this->css .= "\n\n@media (orientation: portrait) {\n" .
				$this->portrait . "}\n";
		}
		
		// Create the different style
		$this->createElement('style', $head, null, "\n" .
			'* { margin:0; box-sizing: border-box; overflow: hidden; pointer-events: all;}' . "\n" .
			'html, body, Threesenter { width: 100%; height: 100%; }' . "\n" . 
			'a { text-decoration: none; display:contents; }' . "\n" . 
			'.layer { position: fixed; width: 100vw; height: 100vh; pointer-events: none; scroll-snap-type: both mandatory;}' . "\n" . 
			'.layer > div { scroll-snap-align: start; }' . "\n" . 
			// '::-webkit-scrollbar { display: none; }'. "\n" . 
			'::-webkit-scrollbar {width: 10px; }' . "\n" . 
			'::-webkit-scrollbar-track { background: #f1f1f1; }' . "\n" . 
			'::-webkit-scrollbar-thumb { background: #888; }' . "\n" . 
			'::-webkit-scrollbar-thumb:hover { background: #555; } ' . "\n" . 
			'img { max-width: 100%; max-height: 100%; object-fit: contain; }' . "\n" . 
			'#footer > div > a { display: inline; }' . "\n" . 
			$data->css . "\n" . 
			$this->css. "\t");

		// Encode the javascript into the page
		$jsonData = json_encode($this->data); //, JSON_PRETTY_PRINT);

		$this->createElement('script', $body, null, "\n" . 
			'let Threesenter_data = ' . $jsonData ."\t");
		$path = $this->getRelativePath($this->path, __DIR__);
		// $this->createElement('script', $body, ['src'=> 'Threesenter.js']);
		
		// Display the HTML document
		$htmlData = $this->dom->saveXML($html, LIBXML_NOEMPTYTAG);
		$htmlData = str_replace("&gt;", ">", $htmlData);
		$htmlData = "<!DOCTYPE html>\n" . $htmlData;
		print($htmlData);
	} 
}

?>
