# Global Geography Data Sources

## Primary source: GeoNames

The project uses the daily GeoNames worldwide extract:

- https://www.geonames.org/export/
- https://download.geonames.org/export/dump/allCountries.zip
- https://download.geonames.org/export/dump/countryInfo.txt

GeoNames states that its data is free, permits commercial use, and is distributed under a Creative Commons Attribution license. Attribution to GeoNames is retained in this project.

The importer uses allCountries.zip rather than a small cities subset. It imports every GeoNames record with feature class P (populated place), which gives much broader coverage than cities1000/cities5000/cities15000.

## Country classification: UN M49

Region and subregion are enriched from the current UN M49 overview:

- https://unstats.un.org/unsd/methodology/m49/overview/

M49 is a statistical/geographical classification and does not itself express a political or legal position concerning country or territory status.

## Why not a manually maintained city list

GeoNames is updated daily and describes the data as provided without a warranty of completeness or accuracy. The project therefore records the exact snapshot date and import statistics rather than claiming an absolute definition of every city in the world.

## Commands

    php artisan geo:import
    php artisan geo:update

Both commands rebuild the geography tables from the current GeoNames snapshot. The importer downloads the source, validates coordinates, normalizes fields, deduplicates source IDs, maps countries, imports populated places, and records statistics in geo_import_runs.

## API

    GET /api/countries
    GET /api/countries/{id}
    GET /api/countries/{id}/cities
    GET /api/cities
    GET /api/cities/search?q=riyadh
    GET /api/cities/nearby?lat=24.7136&lng=46.6753&radius=25

City search checks name, English name, ASCII name, and GeoNames alternate names.

## Dataset size

The README should report counts only after geo:import or geo:update has actually completed against a specific snapshot. Do not hard-code an invented city count.

## Attribution

GeoNames: CC BY 4.0 — https://www.geonames.org/

UN M49: https://unstats.un.org/unsd/methodology/m49/overview/
