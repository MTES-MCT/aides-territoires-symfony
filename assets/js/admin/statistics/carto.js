import { Application } from '@hotwired/stimulus';
import tableController from './table_controller';
import mapController from './map_controller';

require('leaflet/dist/leaflet.js');
$(function() {
    window.Stimulus = Application.start()
    Stimulus.debug = 'debug';
    Stimulus.register("map", mapController)
    Stimulus.register("table", tableController)
  });