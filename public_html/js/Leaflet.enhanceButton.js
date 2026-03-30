/**
 * L.Control.EnhanceButton
 * A self-contained Leaflet control for image enhancement.
 * Injects its own CSS and SVG filters on load.
 */
L.Control.EnhanceButton = L.Control.extend({
    options: {
        position: 'topleft',
        title: 'Map Enhancement',
        icon: '&#10024;',
	storageKey: 'LeafletMapEnhance'
    },

    onAdd: function (map) {
        this._map = map;
        this._setupStyles();
        this._setupSvgFilters();

        // Container
        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-enhance-control');
        this._container = container; // Ensure reference for _hideMenu

        // Toggle Button
        const button = L.DomUtil.create('a', 'enhance-toggle-btn', container);
        button.innerHTML = this.options.icon;
        button.href = '#';
        button.title = this.options.title;
        button.style.fontSize = '16px';

        // Popup Menu
        const menu = L.DomUtil.create('div', 'enhance-menu', container);
        menu.style.display = 'none'; // Hidden by default
        
        // Prevent map clicks/drags when interacting with the menu
        L.DomEvent.disableClickPropagation(container);
        L.DomEvent.disableScrollPropagation(container);

        const options = [
            { id: 'enableNone', val: 'applyNone', label: 'No Enhancement' },
            { id: 'enableUnsharp', val: 'applyUnsharp', label: 'Sharpen' },
            { id: 'enableContrast', val: 'applyContrast', label: 'Contrast Boost' },
            { id: 'enableHighContrast', val: 'applyHighContrast', label: 'High Contrast' }
        ];

	// 1. Determine the initial state from localStorage
        let savedState = 'applyNone';
        if (window.localStorage && this.options.storageKey) {
            savedState = localStorage.getItem(this.options.storageKey) || 'applyNone';
        }

        options.forEach(opt => {
            const wrapper = L.DomUtil.create('div', 'enhance-opt', menu);
            const input = L.DomUtil.create('input', '', wrapper);
            input.type = 'radio';
            input.name = 'enhance_radio';
            input.id = opt.id;
            input.value = opt.val;
            if (opt.val === savedState) input.checked = true;

            const label = L.DomUtil.create('label', '', wrapper);
            label.htmlFor = opt.id;
            label.innerText = opt.label;

            L.DomEvent.on(input, 'change', (e) => {
                this._applyFilter(e.target.value);

		//Save the preference
	        if (window.localStorage && this.options.storageKey) {
        	    localStorage.setItem(this.options.storageKey, e.target.value);
	        }
            });
        });

        // Toggle visibility
        L.DomEvent.on(button, 'click', L.DomEvent.stop).on(button, 'click', (e) => {
            L.DomEvent.stopPropagation(e);

            const isVisible = menu.style.display === 'block';
            if (isVisible) {
                // CLOSE logic
                menu.style.display = 'none';
                // Remove the map listener so it doesn't stay 'primed'
                this._map.off('mousedown touchstart movestart', this._hideMenu, this);
            } else {
                // OPEN logic
                this._closeAllMenus();
                menu.style.display = 'block';
                // Use a named function so we can specifically turn it off later
                this._map.once('mousedown touchstart movestart', this._hideMenu, this);
            }
        });

	// Restore the filter on load
        // We use a timeout or a nextTick to ensure the pane is ready
        setTimeout(() => {
            this._applyFilter(savedState);
        }, 0);

        return container;
    },

    _hideMenu: function() {
        const menu = this._container.querySelector('.enhance-menu');
        if (menu) menu.style.display = 'none';
    },

    _closeAllMenus: function() {
        const menus = document.querySelectorAll('.opacity-menu, .enhance-menu');
        menus.forEach(m => m.style.display = 'none');
    },

    _applyFilter: function (className) {
        const pane = this._map.getPane('tilePane');
        const filterClasses = ['applyUnsharp', 'applyContrast', 'applyHighContrast'];
        
        filterClasses.forEach(cls => L.DomUtil.removeClass(pane, cls));
        
        if (className !== 'applyNone') {
            L.DomUtil.addClass(pane, className);
        }
    },

    _setupStyles: function () {
        if (document.getElementById('leaflet-enhance-style')) return;

        const style = document.createElement('style');
        style.id = 'leaflet-enhance-style';
        style.innerHTML = `
            .leaflet-tile-pane.applyUnsharp { filter: url(#unsharpy); }
            .leaflet-tile-pane.applyHighContrast { filter: url(#highcontrast); }
            .leaflet-tile-pane.applyContrast { filter: brightness(90%) contrast(130%); }

            .leaflet-enhance-control { position: relative; }
            .enhance-toggle-btn { background: #fff; width: 30px; height: 30px; line-height: 30px;
                                   display: block; text-align: center; text-decoration: none; color: #000; }
            .enhance-toggle-btn:hover { background: #f4f4f4; }

            .enhance-menu {
                position: absolute; top: 0; background: white; padding: 10px;
                border: 2px solid rgba(0,0,0,0.2); border-radius: 4px; white-space: nowrap;
                box-shadow: 0 1px 5px rgba(0,0,0,0.4);
            }
            .leaflet-left .enhance-menu { left: 34px; }
            .leaflet-right .enhance-menu { right: 34px; }

            .enhance-opt { margin-bottom: 6px; display: flex; align-items: center; }
            .enhance-opt:last-child { margin-bottom: 0; }
            .enhance-opt label { margin-left: 8px; cursor: pointer; font: 12px/1.5 "Helvetica Neue", Arial, Helvetica, sans-serif; color: #333; }
            .enhance-opt input { cursor: pointer; margin: 0; }
        `;
        document.head.appendChild(style);
    },

    _setupSvgFilters: function () {
        if (document.getElementById('leaflet-enhance-filters')) return;

        const svgHtml = `
            <svg id="leaflet-enhance-filters" style="display:none;" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <filter id="unsharpy">
                        <feGaussianBlur result="blurOut" in="SourceGraphic" stdDeviation="2"/>
                        <feComposite operator="arithmetic" k1="0" k2="1.5" k3="-0.5" k4="0" in="SourceGraphic" in2="blurOut" />
                    </filter>
                    <filter id="highcontrast">
                        <feComponentTransfer>
                            <feFuncR type="gamma" exponent="3.0"/>
                            <feFuncG type="gamma" exponent="3.0"/>
                            <feFuncB type="gamma" exponent="3.0"/>
                        </feComponentTransfer>
                    </filter>
                </defs>
            </svg>`;
        document.body.insertAdjacentHTML('beforeend', svgHtml);
    }
});

// Factory for easy instantiation
L.control.enhanceButton = function (options) {
    return new L.Control.EnhanceButton(options);
};


/////////////////////////////////////////////////////////////////

/**
 * L.Control.OpacityMenu
 * A self-contained Leaflet control to select opacity presets via radio buttons.
 */
L.Control.OpacityMenu = L.Control.extend({
    options: {
        position: 'topleft',
        title: 'Layer Opacity Settings',
        icon: '<i class="fa fa-adjust"></i>', // Using an emoji, but you can use <i class="fa fa-adjust"></i>
        baseMaps: {},
        overlayMaps: {},
	storageKey: 'LeafletMapOpacity'
    },

    onAdd: function (map) {
        this._map = map;
        this._initialValues = null;
        this._setupStyles();

        // Container
        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-opacity-control');
	this._container = container; // Ensure reference for _hideMenu

        // Toggle Button
        const button = L.DomUtil.create('a', 'opacity-toggle-btn', container);
        button.innerHTML = this.options.icon;
        button.href = '#';
        button.title = this.options.title;

        // Popup Menu
        const menu = L.DomUtil.create('div', 'opacity-menu', container);
        menu.style.display = 'none';
        
        L.DomEvent.disableClickPropagation(container);
        L.DomEvent.disableScrollPropagation(container);

        const modes = [
            { id: 'opNominal', label: 'Standard', base: 1.0, over: 1.0 },
            { id: 'opHighlightBase', label: 'Highlight Base Map', base: 1.1, over: 0.6 },
            { id: 'opHighlightOver', label: 'Highlight Overlays', base: 0.6, over: 1.1 }
        ];

        // 1. Determine the initial state from localStorage
        let savedState = 'opNominal';
        if (window.localStorage && this.options.storageKey) {
            savedState = localStorage.getItem(this.options.storageKey) || 'opNominal';
        }

        modes.forEach(mode => {
            const wrapper = L.DomUtil.create('div', 'opacity-opt', menu);
            const input = L.DomUtil.create('input', '', wrapper);
            input.type = 'radio';
            input.name = 'opacity_preset';
            input.id = mode.id;
            if (savedState === mode.id) input.checked = true;

            const label = L.DomUtil.create('label', '', wrapper);
            label.htmlFor = mode.id;
            label.innerText = mode.label;

            L.DomEvent.on(input, 'change', () => {
                this._applyOpacityPreset(mode.base, mode.over);
		//Save the preference
		if (window.localStorage && this.options.storageKey) {
		    localStorage.setItem(this.options.storageKey, mode.id);
		}
            });
        });

        // Toggle visibility
        L.DomEvent.on(button, 'click', L.DomEvent.stop).on(button, 'click', (e) => {
            L.DomEvent.stopPropagation(e);

            const isVisible = menu.style.display === 'block';

            if (isVisible) {
                // CLOSE logic
                menu.style.display = 'none';
                // Remove the map listener so it doesn't stay 'primed'
                this._map.off('mousedown touchstart movestart', this._hideMenu, this);
            } else {
                // OPEN logic
                this._closeAllMenus();
                menu.style.display = 'block';
                // Use a named function so we can specifically turn it off later
                this._map.once('mousedown touchstart movestart', this._hideMenu, this);
            }
        });

	if (savedState != 'opNominal') {
		const mode = modes.find(record => record.id == savedState);
		if (mode) {
			setTimeout(() => {
		            this._applyOpacityPreset(mode.base, mode.over);
	        	}, 0);
		}
	}

        return container;
    },

    _hideMenu: function() {
        const menu = this._container.querySelector('.opacity-menu');
        if (menu) menu.style.display = 'none';
    },

    _closeAllMenus: function() {
        const menus = document.querySelectorAll('.opacity-menu, .enhance-menu');
        menus.forEach(m => m.style.display = 'none');
    },

    _applyOpacityPreset: function (baseFudge, overFudge) {
        // Initialize original values once
        if (!this._initialValues) {
            this._initialValues = new Map();
            const all = { ...this.options.baseMaps, ...this.options.overlayMaps };
            for (let i in all) {
                let layer = all[i];
                let op = (layer.options && layer.options.opacity !== undefined) ? layer.options.opacity : 1;
                this._initialValues.set(layer, op);
            }
        }

        // Apply to Base
        for (let i in this.options.baseMaps) {
            this._updateLayer(this.options.baseMaps[i], baseFudge);
        }
        // Apply to Overlays
        for (let i in this.options.overlayMaps) {
            this._updateLayer(this.options.overlayMaps[i], overFudge);
        }
    },

    _updateLayer: function (layer, fudge) {
        let defaultValue = this._initialValues.get(layer) || 1;
        let value = Math.min(Math.max(defaultValue * fudge, 0), 1);

        if (layer.setOpacity) {
            layer.setOpacity(value);
        } else if (layer.setStyle) {
            layer.setStyle({ opacity: value, fillOpacity: value });
        } else if (layer.options) {
            layer.options.opacity = value;
        }
    },

    _setupStyles: function () {
        if (document.getElementById('leaflet-opacity-style')) return;
        const style = document.createElement('style');
        style.id = 'leaflet-opacity-style';
        style.innerHTML = `
            .leaflet-opacity-control { position: relative; }
            .opacity-toggle-btn { background: #fff; width: 30px; height: 30px; line-height: 30px;
                                   display: block; text-align: center; text-decoration: none; color: #000; font-size: 16px; }
            .opacity-toggle-btn:hover { background: #f4f4f4; }

            .opacity-menu {
                position: absolute; top: 0; background: white; padding: 10px;
                border: 2px solid rgba(0,0,0,0.2); border-radius: 4px; white-space: nowrap;
                box-shadow: 0 1px 5px rgba(0,0,0,0.4);
            }
            .leaflet-left .opacity-menu { left: 34px; }
            .leaflet-right .opacity-menu { right: 34px; }

            .opacity-opt { margin-bottom: 6px; display: flex; align-items: center; }
            .opacity-opt:last-child { margin-bottom: 0; }
            .opacity-opt label { margin-left: 8px; cursor: pointer; font: 12px/1.5 "Helvetica Neue", Arial, Helvetica, sans-serif; color: #333; }
            .opacity-opt input { cursor: pointer; margin: 0; }
        `;
        document.head.appendChild(style);
    }
});

L.control.opacityMenu = function (options) {
    return new L.Control.OpacityMenu(options);
};
