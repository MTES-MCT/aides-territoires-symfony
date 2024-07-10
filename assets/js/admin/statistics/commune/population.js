require('leaflet/dist/leaflet.js');

$(function() {
    var map = L.map('map').setView([46.603354, 1.888334], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    cities.forEach(function(city) {
        var population = city.population;
        var size = Math.log(population) * 2; // Taille du marqueur basée sur la population
        var circle = L.circle([city.latitude, city.longitude], {
            color: 'blue',
            fillColor: '#30f',
            fillOpacity: 0.5,
            radius: size * 500 // Ajustez le facteur selon vos besoins
        }).addTo(map).bindPopup(city.name + '<br>Population: ' + population);
    });

    epcis.forEach(function(city) {
        var population = city.population;
        var size = Math.log(population) * 2; // Taille du marqueur basée sur la population
        var circle = L.circle([city.latitude, city.longitude], {
            color: 'yellow',
            fillColor: 'yellow',
            fillOpacity: 0.5,
            radius: size * 1000 // Ajustez le facteur selon vos besoins
        }).addTo(map).bindPopup(city.name + '<br>Population: ' + population);
    });

    var legend = L.control({position: 'bottomright'});

    legend.onAdd = function (map) {
        var div = L.DomUtil.create('div', 'legend');
        div.innerHTML += '<i style="background: blue"></i> Commune<br>';
        div.innerHTML += '<i style="background: yellow"></i> EPCI<br>';
        return div;
    };

    legend.addTo(map);
});