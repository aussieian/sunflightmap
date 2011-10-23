//
// DayNightMapType()
//
// This draws the daynight shading on a google map. This is a V3 MapType object
// It uses *no* external resources. it is CPU thirsty.
//
// License: BSD -- i.e. you can do anything you like except claim that you wrote it.
// I would appreciate bug fixes.
//
// Copyright 2010 Philip Gladstone
// 
// Typical use:
//      var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
//      var dn = new DayNightMapType();	// Instantiate the day/night maptype object
//      map.overlayMapTypes.insertAt(0, dn);  // insert it onto the actual map
//      dn.setMap(map);		 // required
//      dn.setAutoRefresh(300);  // In seconds between grey line updates
//
// based off: http://pskreporter.info/grid/test.html

function DayNightMapType(UTCTime, minutesOffset) {
    this.max_alt = 1.05 * Math.PI / 180.0;
    this.min_alt = -1.05 * Math.PI / 180.0;
    this.opacity = 50; 
    this.lighturl = "http://night-shade.appspot.com.nyud.net/nightshade/";
    this.lighturl = "http://night-shade.appspot.com/nightshade/";
    this.showingLights = 0;
	this.calcCurrentTime(UTCTime, minutesOffset);
}

var G_vmlCanvasManager;

(function() {

    var MERCATOR_RANGE = 256;

    function sinh (arg) {
	// Returns the hyperbolic sine of the number, defined as (exp(number) - exp(-number))/2  
	return (Math.exp(arg) - Math.exp(-arg))/2;
    }
     
    function bound(value, opt_min, opt_max) {
      if (opt_min != null) value = Math.max(value, opt_min);
      if (opt_max != null) value = Math.min(value, opt_max);
      return value;
    }
     
    function degreesToRadians(deg) {
      return deg * (Math.PI / 180);
    }
     
    function radiansToDegrees(rad) {
      return rad / (Math.PI / 180);
    }
     
    function MercatorProjection() {
      this.pixelOrigin_ = new google.maps.Point(
	  MERCATOR_RANGE / 2, MERCATOR_RANGE / 2);
      this.pixelsPerLonDegree_ = MERCATOR_RANGE / 360;
      this.pixelsPerLonRadian_ = MERCATOR_RANGE / (2 * Math.PI);
    };
     
    MercatorProjection.prototype.fromLatLngToDivPixel = function(latLng, opt_point, zoom) {
      var me = this;

      var point = opt_point || new google.maps.Point(0, 0);

      var origin = me.pixelOrigin_;
      point.x = origin.x + latLng.lng() * me.pixelsPerLonDegree_ * Math.pow(2, zoom);
      // NOTE(appleton): Truncating to 0.9999 effectively limits latitude to
      // 89.189.  This is about a third of a tile past the edge of the world tile.
      var siny = bound(Math.sin(degreesToRadians(latLng.lat())), -0.9999, 0.9999);
      point.y = origin.y + 0.5 * Math.log((1 + siny) / (1 - siny)) * -me.pixelsPerLonRadian_ * Math.pow(2, zoom);
      return point;
    };
     
    MercatorProjection.prototype.fromDivPixelToLatLng = function(pixel, zoom) {
      var me = this;
      
      var origin = me.pixelOrigin_;
      var scale = Math.pow(2, zoom);
      var lng = (pixel.x / scale - origin.x) / me.pixelsPerLonDegree_;
      var latRadians = (pixel.y / scale - origin.y) / -me.pixelsPerLonRadian_;
      var lat = radiansToDegrees(2 * Math.atan(Math.exp(latRadians)) - Math.PI / 2);
      return new google.maps.LatLng(lat, lng);
    };

    var fmod = function (d, v) {
        var q = Math.floor(d / v);
        return d - q * v;
    }

    DayNightMapType.prototype.getCanvas = function (oldcanvas) {
	if (oldcanvas) {
            var ctx = oldcanvas.getContext("2d");

	    ctx.globalCompositeOperation = "source-over";
            ctx.clearRect(0, 0, 256, 256);
	    return oldcanvas;
        }
	var canvas = this.ownerDocument.createElement('canvas');
	canvas.setAttribute('width',256);  
	canvas.setAttribute('height',256);  

	return canvas;
    }

    DayNightMapType.prototype.getUniformCanvas = function(opacity, oldcanvas) {
	var canvas = this.getCanvas(oldcanvas);
	if (opacity > 0) {
            var ctx = canvas.getContext("2d");

            ctx.fillStyle = "rgba(0, 0, 0, " + (opacity / 100) + ")";
            ctx.fillRect(0, 0, 256, 256);
	}

	return canvas;
    }

    DayNightMapType.prototype.getAltitude = function (ll, dt) {
        var fLatitude = degreesToRadians(ll.lat());
        var fLongitude = degreesToRadians(ll.lng());

        // Calculate difference (in minutes) from reference longitude.
        var fDifference = (((fLongitude) * 180/Math.PI) * 4) / 60.0;

        // Caculate solar time.
        var fSolarTime = this.fLocalTime + this.fEquation + fDifference;

        // Calculate hour angle.
        var fHourAngle = (15 * (fSolarTime - 12)) * (Math.PI/180.0);

        // Calculate current altitude.
        var cc = Math.cos(this.fDeclination) * Math.cos(fLatitude);
        t = (Math.sin(this.fDeclination) * Math.sin(fLatitude)) + (cc * Math.cos(fHourAngle));
        var fAltitude = Math.asin(t);

        var result = new Object();
        result.altitude = fAltitude;
        result.slopeEast = -cc * Math.sin(fHourAngle);

        return result;
    }

    var sign = function (x) {
        if (x < 0) {
            return -1;
        }

        return 1;
    }

    DayNightMapType.prototype.cityLightsTileLoaded = function(pt, zoom, oldcanvas, im) 
    {
	//log.info("got image for " + pt + " zoom " + zoom);
	var canvas = this.getShadedTileObject(pt, zoom, oldcanvas);
	var ctx = canvas.getContext("2d");
	ctx.globalCompositeOperation = "source-in";
	ctx.drawImage(im, 0, 0);
    }

    DayNightMapType.prototype.getTileObject = function(opt, zoom, oldcanvas) 
    {
	var pt = new google.maps.Point(opt.x, opt.y);
	pt.x = pt.x % (1 << zoom);
	if (pt.x < 0) {
	    pt.x = pt.x + (1 << zoom);
	}
	//log.info("Gettile: " + opt + " => " + pt);
	if (pt.x < 0 || pt.y < 0 || pt.x >= (1 << zoom) || pt.y >= (1 << zoom)) {
	    return null;
	}

	if (!this.showingLights || zoom > 6) {
	    return this.getShadedTileObject(pt, zoom, oldcanvas);
	}

	if (this.isDaytimeTile(pt, zoom)) {
	    return this.getUniformCanvas(0, oldcanvas);
	}

	var canvas;

	if (!oldcanvas) {
	    canvas = this.getCanvas(oldcanvas);
	} else {
	    canvas = oldcanvas;
	}

	// We need to fetch the city lights tile, and when we have it, reshade the original canvas, and composite in the image.
	var im = document.createElement('img');
	var me = this;
	im.onload = function() { 
	    me.cityLightsTileLoaded(pt, zoom, canvas, im);
	};
	im.onerror = function() {
	    // Do the night shading anyway.....
	    me.getShadedTileObject(pt, zoom, canvas);
	    //log.info("failed to get image from " + im.src);
	}
	//im.src = this.lighturl + pt.x + "-" + pt.y + "-" + zoom + "-static-100n.png"; 
	//im.src = "http://night-shade.appspot.com/nightshade/1-3-2-static-100n.png";
	im.src = "/images/night_map_tile.png";
	//log.info("Starting load of " + im.src);

	return canvas;
    }

    DayNightMapType.prototype.isDaytimeTile = function(pt, zoom) 
    { 
        var proj = this.projection;
        var tl = proj.fromDivPixelToLatLng(new google.maps.Point(pt.x * 256, pt.y * 256), zoom);
        var tr = proj.fromDivPixelToLatLng(new google.maps.Point(pt.x * 256 + 255, pt.y * 256), zoom);
        var bl = proj.fromDivPixelToLatLng(new google.maps.Point(pt.x * 256, pt.y * 256 + 255), zoom);
        var br = proj.fromDivPixelToLatLng(new google.maps.Point(pt.x * 256 + 255, pt.y * 256 + 255), zoom);

        var tla = this.getAltitude(tl);
        var tra = this.getAltitude(tr);
        var bla = this.getAltitude(bl);
        var bra = this.getAltitude(br);

        if (tla.altitude > this.max_alt && tra.altitude > this.max_alt &&
            bla.altitude > this.max_alt && bra.altitude > this.max_alt) {
            if (tla.slopeEast >= 0 || (tla.slopeEast < 0 && tra.slopeEast < 0)) {
                if (bla.slopeEast >= 0 || (bla.slopeEast < 0 && bra.slopeEast < 0)) {
                    return 1;
                }
            }
        }

	return 0;
    }

    DayNightMapType.prototype.getShadedTileObject = function(pt, zoom, oldcanvas) 
    { 
        var proj = this.projection;
        var tl = proj.fromDivPixelToLatLng(new google.maps.Point(pt.x * 256, pt.y * 256), zoom);
        var tr = proj.fromDivPixelToLatLng(new google.maps.Point(pt.x * 256 + 255, pt.y * 256), zoom);
        var bl = proj.fromDivPixelToLatLng(new google.maps.Point(pt.x * 256, pt.y * 256 + 255), zoom);
        var br = proj.fromDivPixelToLatLng(new google.maps.Point(pt.x * 256 + 255, pt.y * 256 + 255), zoom);

        var tla = this.getAltitude(tl);
        var tra = this.getAltitude(tr);
        var bla = this.getAltitude(bl);
        var bra = this.getAltitude(br);

        if (tla.altitude > this.max_alt && tra.altitude > this.max_alt &&
            bla.altitude > this.max_alt && bra.altitude > this.max_alt) {
            if (tla.slopeEast >= 0 || (tla.slopeEast < 0 && tra.slopeEast < 0)) {
                if (bla.slopeEast >= 0 || (bla.slopeEast < 0 && bra.slopeEast < 0)) {
                    return this.getUniformCanvas(0, oldcanvas);
                }
            }
        }
        if (tla.altitude < this.min_alt && tra.altitude < this.min_alt &&
            bla.altitude < this.min_alt && bra.altitude < this.min_alt) {
            if (tla.slopeEast < 0 || (tla.slopeEast >= 0 && tra.slopeEast >= 0)) {
                if (bla.slopeEast < 0 || (bla.slopeEast >= 0 && bra.slopeEast >= 0)) {
                    return this.getUniformCanvas(this.opacity, oldcanvas);
                }
            }
        }

	// We need to make a canvas
	var canvas = this.getCanvas(oldcanvas);
	this.paintTile(canvas, pt, zoom);

	return canvas;
    };

	DayNightMapType.prototype.calcCurrentTime = function (UTCTime, minutesOffset) {
		this.minutesOffset = minutesOffset;
		this.currentTime = UTCTime;
		this.currentTime.setMinutes(UTCTime.getMinutes() + this.minutesOffset);
	}
	
    DayNightMapType.prototype.setCurrentTime = function () {
	if (this.map) {
	    if (this.map.getMapTypeId() == "roadmap") {
		this.opacity = 50;
	    } else {
		this.opacity = 60;
	    }
	}
        //this.currentTime = new Date(this.timeInterval * 1000 * Math.round(new Date() / (this.timeInterval * 1000)));
        //this.currentTime = new Date();
		//alert(this.currentTime);
		// add offset
		//this.currentTime += this.minutesOffset * 60; // add seconds
		//alert(this.minutesOffset);
		//this.currentTime.setMinutes(this.currentTime.getMinutes() + this.minutesOffset);
		//console.log(this.minutesOffset);
		//console.log(this.currentTime);
		
        // Get julian date.
        var fJulianDate = this.currentTime / (1000 * 86400.0);

        // Get local time value.
        this.fLocalTime = (this.currentTime % 86400000) / (1000 * 3600.0);


        ////////////////////////////////////////////////////////////
        // CALCULATE SOLAR VALUES
        ////////////////////////////////////////////////////////////

        // Calculate solar declination as per Carruthers et al.
        var t = 2 * Math.PI * fmod((fJulianDate - 1) / 365.25, 1);

        var fDeclination = (0.322003
              - 22.971 * Math.cos(t)
              - 0.357898 * Math.cos(2*t)
              - 0.14398 * Math.cos(3*t)
              + 3.94638 * Math.sin(t)
              + 0.019334 * Math.sin(2*t)
              + 0.05928 * Math.sin(3*t)
              );

        // Convert degrees to radians.
        if (fDeclination > 89.9) fDeclination = 89.9;
        if (fDeclination < -89.9) fDeclination = -89.9;

        // Convert to radians.
        this.fDeclination = fDeclination * (Math.PI/180.0);

        // Calculate the equation of time as per Carruthers et al.
        t = fmod(279.134 + 0.985647 * fJulianDate, 360) * (Math.PI/180.0);

        var fEquation = (5.0323
              - 100.976 * Math.sin(t)
              + 595.275 * Math.sin(2*t)
              + 3.6858 * Math.sin(3*t)
              - 12.47 * Math.sin(4*t)
              - 430.847 * Math.cos(t)
              + 12.5024 * Math.cos(2*t)
              + 18.25 * Math.cos(3*t)
              );

        // Convert seconds to hours.
        this.fEquation = fEquation / 3600.00;
    };

    DayNightMapType.prototype.paintTile = function(canvas, tile, zoom) {
        var max_opacity = Math.floor(this.opacity * 255 / 100);

        var center = 128  * (1 << zoom);

	var degperrad = 180 / Math.PI;


	var ctx = canvas.getContext("2d");
	var canvasData = ctx.createImageData(canvas.width, canvas.height);

	var altrange = this.max_alt - this.min_alt;

	var altrangefactor = max_opacity / altrange;

	var fLocalTimeEquation = this.fLocalTime + this.fEquation - 12;

	var centerscale = Math.PI / center;

	var xscale = centerscale * degperrad * 4 / 60.0

	// precompute the hour angles
	var fHourAngle = new Array();

	for (var xpix = tile.x * 256 - center; xpix < tile.x * 256 + 256 - center; xpix++) { 
	    fHourAngle.push(Math.cos((15 * (fLocalTimeEquation + xpix * xscale)) / degperrad));
	}

	var fCosDeclination = Math.cos(this.fDeclination)
	var fSinDeclination = Math.sin(this.fDeclination)

	var cidx = 3;  // We only manipulate the transpareny

	//log.info("max opacity = " + max_opacity);

	for (var ypix = -(tile.y * 256 - center); ypix > -(tile.y * 256 + 256 - center); ypix--) {
	    // fLatitude = math.atan(math.sinh(ypix * centerscale));

	    // cc = math.cos(fDeclination) * math.cos(fLatitude);
	    // tfixed = math.sin(fDeclination) * math.sin(fLatitude);

	    var tanval = sinh(ypix * centerscale);
	    var hypot = Math.sqrt(1 + tanval * tanval);
	    var cc = fCosDeclination / hypot;
	    var tfixed = fSinDeclination * tanval / hypot;

 	    //for xpix in range(x * 256 - center, x * 256 + 256 - center):
	    for (var xi = 0; xi < 256; xi++) {
	        // fLongitude = xpix * centerscale;

	        //  Calculate difference (in minutes) from reference longitude.
	        // fDifference = (xpix * centerscale * degperrad * 4) / 60.0

	        //  Caculate solar time.
	        // fSolarTime = fLocalTimeEquation + fDifference

	        //  Calculate hour angle.
	        // fHourAngle = (15 * (fLocalTimeEquation + xpix * xscale)) / degperrad;

	        //  Calculate current altitude.
	        // t = tfixed + (cc * math.cos(fHourAngle));
	        t = tfixed + (cc * fHourAngle[xi]);

	        if (t <= this.min_alt) {   // should be min_alt_sin
		    canvasData.data[cidx] = max_opacity
	        } else if (t >= this.max_alt) {   // should be max_alt_sin
		    // data is already zero
	        } else {
		    // v = math.floor((max_alt - math.asin(t)) * 256 / altrange);
		    v = ((this.max_alt - t) * altrangefactor);   // at small values math.asin(x) == x

		    canvasData.data[cidx] = Math.floor(v);    // note that v is limited to [0, 255]
		}

		cidx += 4;
	    }
	}

	ctx.putImageData(canvasData, 0, 0);
    }
		

    DayNightMapType.prototype.tileSize = new google.maps.Size(256,256);
    DayNightMapType.prototype.maxZoom = 10;

    DayNightMapType.prototype.redoTiles = function() {
	var mapdiv = this.map.getDiv();

	this.setCurrentTime();

	// Find all our divs underneath this, and replace with new values
	var canvases = mapdiv.getElementsByTagName('canvas');

	var did = 0;
	var count = 0;

	for (var i in canvases) {
	    var canvas = canvases[i];

	    count++;

	    if (canvas.daynightcoord) {
		this.getTileObject(canvas.daynightcoord, canvas.zoom, canvas);
		did++;
	    }
	}
	//log.info("Redrawing all did " + did + " out of " + count);
    }

    DayNightMapType.prototype.getTile = function(coord, zoom, ownerDocument) {
	this.ownerDocument = ownerDocument;
	//if (!this.currentTime) {
	    this.setCurrentTime();
	//}
        if (this.projection == null) {
	    alert('projection is null in getTile');
        }

	//log.info("getTile: " + coord + ", light=" + this.showlights);

	//var div = ownerDocument.createElement('div');
	var canvas = this.getTileObject(coord, zoom);
	//div.appendChild(this.getTileObject(coord, zoom));

	if (canvas) {
	    canvas.daynightcoord = coord;
	    canvas.zoom = zoom;
	} else {
	    canvas = ownerDocument.createElement('div');
	}

	return canvas;
    }

    DayNightMapType.prototype.setShowLights = function (showlights) {
	if (this.showingLights != showlights) {
	    this.showingLights = showlights;
	    this.redoTiles();
	}
    }

    DayNightMapType.prototype.setAutoRefresh = function (refreshTime) {
	if (this.intervalId) {
	    clearInterval(this.intervalId);
	    this.intervalId = 0;
	}
	if (refreshTime) {
	    var me = this;
	    this.intervalId = setInterval(function () { me.redoTiles(); }, refreshTime * 1000);
	}
    }

    DayNightMapType.prototype.setMap = function(map) {
	this.map = map;
	this.ownerDocument = map.getDiv().ownerDocument;
	var me = this;
	google.maps.event.addListener(map, "maptypeid_changed", function () { me.redoTiles(); });
    }

    DayNightMapType.prototype.name = "Day/Night Terminator";

    // Temp till the bug is fixed.
    DayNightMapType.prototype.projection = new MercatorProjection();

})();


