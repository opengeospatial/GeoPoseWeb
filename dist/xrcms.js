
/** Defines a XRCMS instance */
class XRCMS {

	/** The list of XRCMS instances.*/
	static instances = [];

	/** Initializes a new XRCMS instance.
	 * @param data The initialization data. */
	 constructor(data = {}) {


		this.data = data;
		if (xrcms_data) this.data = xrcms_data;

		this.element = document.getElementById(this.data.id);

		// Create the renderer
		this.renderer = new THREE.WebGLRenderer({antialias:true});
		this.renderer.xr.enabled = true;
		this.renderer.autoClear = false;
		this.renderer.domElement.id = "XRCMS Renderer";
		this.element.appendChild(this.renderer.domElement);
		this.renderer.setAnimationLoop(this.update);

		// Parse the layers
		this.data.layers.forEach(layer => {
			let elem = document.getElementById(layer.id);
			
			// this.element.removeChild(elem);

			
		});
		


		// Resize the renderer
		document.onresize = this.resize; this.resize();

		// Add this instance to the list
		XRCMS.instances.push(this);
	}


	/** Initializes the XRCMS environment. 
	 * @param data The initialization data. */
	static init(data) { return new XRCMS(data); }


	/** Updates the XRCMS environment. 
	 * @param time The current time. */
	update(time) {


	}
	
	/** Resizes the XRCMS environment. */
	resize() {

		this.width = this.element.clientWidth;
		this.height = this.element.clientHeight;

		// Set the size of the renderer
		this.renderer.setSize(this.width, this.height);
		this.renderer.setPixelRatio(window.devicePixelRatio);

		// Set the aspect ratio for the different cameras 
	}
}

// ---------------------------------------------------------------- ENTRY POINT 

// Initialize the XRCMS environment
XRCMS.init();