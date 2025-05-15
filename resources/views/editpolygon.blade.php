@extends('layout.template')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">
<style>
    #map {
        width: 100%;
        height: calc(100vh - 56px);
    }
</style>
@endsection

@section('content')
<div id="map"></div>

<!-- Modal Edit Polygon -->
<div class="modal fade" id="editpolygonModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">Edit Polygon</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('polygons.update', $polygon->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" value="{{ $polygon->name }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ $polygon->description }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Geometry</label>
                        <textarea class="form-control" name="geom_polygon" id="geom_polygon" rows="3">{{ $polygon->geom }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" class="form-control" name="image" onchange="document.getElementById('preview-image-polygon').src = window.URL.createObjectURL(this.files[0])">
                        <img src="{{ asset('storage/images/'.$polygon->image) }}" alt="" id="preview-image-polygon" class="img-thumbnail mt-2" width="400">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                        <a href="{{ route('polygons.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script src="https://unpkg.com/@terraformer/wkt"></script>
    <script>
        var map = L.map('map').setView([-6.176533094143277, 106.83062228220317], 15);

        var baseMaps = {
            "OpenStreetMap": L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
            "Esri WorldImagery": L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'),
            "CartoDB DarkMatter": L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png')
        };

        baseMaps["OpenStreetMap"].addTo(map);
        L.control.layers(baseMaps).addTo(map);

        var drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        var drawControl = new L.Control.Draw({
            draw: false,
            edit: {
                featureGroup: drawnItems,
                edit: true,
                remove: false
            }
        });

        map.addControl(drawControl);

        map.on('draw:edited', function(e) {
            e.layers.eachLayer(function(layer) {
                var geo = layer.toGeoJSON();
                var wkt = Terraformer.geojsonToWKT(geo.geometry);
                var props = geo.properties;

                drawnItems.addLayer(layer);

                $('#name').val(props.name);
                $('#description').val(props.description);
                $('#geom_polygon').val(wkt);
                $('#preview-image-polygon').attr('src', "{{ asset('storage/images') }}/" + props.image);

                $('#editpolygonModal').modal('show');
            });
        });

        var polygon = L.geoJson(null, {
            onEachFeature: function(feature, layer) {
                drawnItems.addLayer(layer);
                var props = feature.properties;
                var wkt = Terraformer.geojsonToWKT(feature.geometry);

                layer.on('click', function() {
                    $('#name').val(props.name);
                    $('#description').val(props.description);
                    $('#geom_polygon').val(wkt);
                    $('#preview-image-polygon').attr('src', "{{ asset('storage/images') }}/" + props.image);
                    $('#editpolygonModal').modal('show');
                });
            }
        });

        $.getJSON("{{ route('api.polygon', $id) }}", function(data) {
            polygon.addData(data);
            map.addLayer(polygon);
            map.fitBounds(polygon.getBounds(), { padding: [100, 100] });
        });
    </script>
@endsection
