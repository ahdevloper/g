<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Global Geography

This project includes a global countries and populated-places database built from open GeoNames data.

### Coverage

- Countries: imported from the current GeoNames country extract.
- Places: every GeoNames record with feature class P in allCountries.zip, rather than a small hand-maintained city list.
- Country region/subregion: enriched from the current UN M49 classification.
- Search: name, English name, ASCII name and GeoNames alternate names.
- Nearby search: latitude/longitude bounding-box prefilter plus Haversine distance.
- Pagination and database indexes are used to avoid loading the full dataset in requests.

### Import

Run migrations first, then:

    php artisan migrate
    php artisan geo:import

For a later refresh:

    php artisan geo:update

The importer downloads the current source snapshot, validates coordinates, normalizes records, detects duplicate source IDs, maps country codes, imports populated places in batches, and writes an import report to geo_import_runs.

The complete GeoNames extract is intentionally not committed to Git because the generated dataset is large and changes daily. The repository contains the reproducible importer instead.

### API

    GET /api/countries
    GET /api/countries/{id}
    GET /api/countries/{id}/cities
    GET /api/cities
    GET /api/cities/search?q=riyadh
    GET /api/cities/nearby?lat=24.7136&lng=46.6753&radius=25

### Data and licenses

GeoNames data is free, permits commercial use, and requires attribution under its Creative Commons Attribution license. The application records GeoNames as the source and CC BY 4.0 as the data license.

UN M49 is used for region/subregion classification.

See docs/geo-data-sources.md for source details and update instructions.

### Statistics

Do not publish a hard-coded city count. Run geo:import or geo:update against a named snapshot and use the resulting import report as the source of truth.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax.

## License

The application code remains under the repository's existing license. Third-party geographic data retains its own attribution and licensing terms.
