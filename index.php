<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebGIS LeafletJS dengan Data MySQL</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <!-- Leaflet Fullscreen Plugin CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.fullscreen/1.5.0/Control.FullScreen.min.css" />
    <!-- Leaflet Control Search CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <!-- Custom CSS -->
    <style>
        #map {
            height: 600px;
            width: 100%;
            margin: 20px auto;
            border: 2px solid #333;
            box-shadow: 2px 2px 8px rgba(0,0,0,0.5);
        }
        .info {
            padding: 8px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 8px rgba(0,0,0,0.3);
        }
        .info h4 {
            margin: 0 0 5px;
            font-size: 18px;
        }
        body {
            font-family: Arial, sans-serif;
        }
        h1 {
            text-align: center;
        }
        #markerForm {
            display: none; /* Tersembunyi secara default */
            position: absolute;
            background: white;
            padding: 10px;
            border: 1px solid #333;
            z-index: 1000;
        }
    </style>
</head>
<body>

<h1>WebGIS Interaktif dengan LeafletJS dan MySQL</h1>
<div id="map"></div>

<!-- Form untuk menambahkan titik -->
<div id="markerForm">
    <h3>Tambah Titik</h3>
    <label for="kecamatan">Kecamatan:</label>
    <input type="text" id="kecamatan" required><br>
    <label for="luas">Luas (km²):</label>
    <input type="text" id="luas" required><br>
    <label for="jumlah_penduduk">Jumlah Penduduk:</label>
    <input type="text" id="jumlah_penduduk" required><br>
    <button id="saveMarker">Simpan Titik</button>
    <button id="cancelMarker">Batal</button>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<!-- Leaflet Marker Cluster Plugin -->
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
<!-- Leaflet Fullscreen Plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.fullscreen/1.5.0/Control.FullScreen.min.js"></script>
<!-- Leaflet Control Search JS -->
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<?php
// Memanggil koneksi database
include 'db_connect.php';

// Query untuk mengambil data dari tabel penduduk
$sql = "SELECT kecamatan, longitude, latitude, luas, jumlah_penduduk FROM penduduk";
$result = $conn->query($sql);

// Menyimpan data dalam array
$data = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}
$conn->close();
?>

<script>
    // Inisialisasi peta dan atur titik awal
    var map = L.map('map', {
        fullscreenControl: true,
        center: [-7.797068, 110.370529],
        zoom: 12
    });

    // Menambahkan beberapa tile layer
    var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Menambahkan kontrol layer untuk basemap
    var baseMaps = {
        "OpenStreetMap": osm
    };
    L.control.layers(baseMaps).addTo(map);

    // Membuat cluster untuk marker
    var markers = L.markerClusterGroup();

    // Data dari PHP (menggunakan JSON untuk mengonversi data dari PHP ke JavaScript)
    var data = <?php echo json_encode($data); ?>;

    // Custom icon untuk marker
    var customIcon = L.icon({
        iconUrl: 'https://cdn4.iconfinder.com/data/icons/small-n-flat/24/map-marker-512.png',
        iconSize: [32, 32]
    });

    // Loop melalui setiap lokasi dan tambahkan marker
    data.forEach(function(item) {
        var marker = L.marker([item.latitude, item.longitude], { icon: customIcon })
            .bindPopup("<div class='info'><h4>Kecamatan: " + item.kecamatan + "</h4>" +
                       "<b>Luas:</b> " + item.luas + " km²<br>" +
                       "<b>Jumlah Penduduk:</b> " + item.jumlah_penduduk + "</div>");

        markers.addLayer(marker);
    });

    // Menambahkan cluster ke peta
    map.addLayer(markers);

    // Menambahkan kontrol pencarian
    var controlSearch = L.Control.geocoder({
        defaultMarkGeocode: true
    }).addTo(map);

    // Menambahkan kontrol zoom
    L.control.zoom({
        position: 'topright'
    }).addTo(map);

    // Menangani klik pada peta untuk menambahkan marker
    map.on('click', function(e) {
        // Tampilkan form dan set posisi berdasarkan klik
        document.getElementById('markerForm').style.display = 'block';
        document.getElementById('markerForm').style.left = e.originalEvent.pageX + 'px';
        document.getElementById('markerForm').style.top = e.originalEvent.pageY + 'px';

        // Simpan koordinat klik untuk digunakan saat menyimpan data
        document.getElementById('markerForm').dataset.lat = e.latlng.lat;
        document.getElementById('markerForm').dataset.lng = e.latlng.lng;
    });

    // Menangani penyimpanan marker
    document.getElementById('saveMarker').addEventListener('click', function() {
        var kecamatan = document.getElementById('kecamatan').value;
        var luas = document.getElementById('luas').value;
        var jumlah_penduduk = document.getElementById('jumlah_penduduk').value;
        var lat = document.getElementById('markerForm').dataset.lat;
        var lng = document.getElementById('markerForm').dataset.lng;

        // Mengirim data ke server menggunakan AJAX
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "save_marker.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                // Tambahkan marker baru ke peta
                var newMarker = L.marker([lat, lng], { icon: customIcon })
                    .bindPopup("<div class='info'><h4>Kecamatan: " + kecamatan + "</h4>" +
                               "<b>Luas:</b> " + luas + " km²<br>" +
                               "<b>Jumlah Penduduk:</b> " + jumlah_penduduk + "</div>");
                markers.addLayer(newMarker);
                map.addLayer(markers);

                // Reset form dan sembunyikan
                document.getElementById('markerForm').style.display = 'none';
                document.getElementById('markerForm').reset();
            }
        };
        xhr.send("kecamatan=" + kecamatan + "&luas=" + luas + "&jumlah_penduduk=" + jumlah_penduduk + "&latitude=" + lat + "&longitude=" + lng);
    });

    // Menangani batal penyimpanan
    document.getElementById('cancelMarker').addEventListener('click', function() {
        document.getElementById('markerForm').style.display = 'none';
        document.getElementById('markerForm').reset();
    });
</script>

</body>
</html>
