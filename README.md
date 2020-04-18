# ![Mascot](https://user-images.githubusercontent.com/2371345/65699309-4752e380-e054-11e9-8bb1-d1aee8e2724e.png) Controlled Access Terms

[![Build Status][1]](https://travis-ci.com/Islandora/controlled_access_terms)
[![Contribution Guidelines][2]](./CONTRIBUTING.md)
[![LICENSE][3]](./LICENSE)

## Introduction

This Drupal 8 module creates vocabularies to represent common named entities
in archival description (Corporate Bodies, Families, and Persons) as well as
subject terms.

It is intended to be used in conjunction with both the [ArchivesSpace/Drupal 8
Integration project](https://github.com/UNLV-Libraries/archivesspace-drupal) and
[Islandora 8](https://github.com/Islandora/islandora/tree/8.x-1.x).


## Requirements

This module requires the following modules:

- [name](https://www.drupal.org/project/name)
- [geolocation](https://www.drupal.org/project/geolocation)
- [token](https://www.drupal.org/project/token)

## Installation

Download and install [as with other Drupal modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).

For example, using composer from the Drupal site's web directory:

```
$ composer require islandora/controlled_access_terms
$ drush en -y controlled_access_terms
```

Enable controlled_access_terms_defaults to create the default vocabularies.

## Configuration

Provided vocabularies and fields may be configured in the same manner as
other Drupal 8 vocabularies.

## Provided Vocabularies

Below is a list of the vocabularies provided by controlled_access_terms_defaults.
The fields with "EDTF" accept and display dates corresponding
to the Library of Congress 2018 Extended Date/Time Format Specification (EDTF).
See the section below for more information on EDTF.

- Corporate Body
  - Preferred Name (Name)
  - Type
    - Organizational Unit (org:OrganizationalUnit)
    - Airline (schema:Airline)
    - Corporation (schema:Corporation)
    - Educational Organization (schema:EducationalOrganization)
    - Government Organization (schema:GovernmentOrganization)
    - LocalBusiness (schema:LocalBusiness)
    - Medical Organization (schema:MedicalOrganization)
    - Non-Governmental Organization (schema:NGO)
    - Performing Group (schema:PerformingGroup)
    - Sports Organization (schema:SportsOrganization)
    - Sports Team (schema:SportsTeam)
  - Authority Link
  - Founding Date (EDTF)
  - Dissolution Date (EDTF)
  - Alternate Name
  - Description
  - Related Entities
- Family
  - Display Label (Name)
  - Description
  - Date Begin (EDTF)
  - Date End (EDTF)
  - Authority Link
  - Relation
- Person
  - (Display) Name
  - Authority Link
  - Preferred Name
  - Alternate Name
  - Description
  - Birth Date (EDTF)
  - Death Date (EDTF)
  - Relationships
- Geographic Location
  - Name (Title)
  - Authority Link
  - Latitude/Longitude ([WGS 84](https://en.wikipedia.org/wiki/World_Geodetic_System))
  - Description
  - Alternate Name
  - Broader
- Subject
  - Name
  - Language
  - Description
  - Authority Link

## Extended Date/Time Format (EDTF)

The Library of Congress created the [Extended Date/Time Format Specification](http://www.loc.gov/standards/datetime/edtf.html)
which was subsequently incorporated with ISO 8601-2019. This modules provides
a custom EDTF field type with a corresponding formatter (for display) and widget
(for data entry).

Both the formatter and widget include settings for controlling
how the EDTF is entered and displayed. For example, the widget allows EDTF
values to use intervals; however, the widget settings (accessible through the
bundle's form display page) can restrict the field to only accept single-dates.

Note: widget settings will not apply to data imported through other means (e.g.
the Migrate API or REST-based updates).

The formatter settings allow administrators to control how the date is
displayed. The default setting is YYYY-MM-DD (e.g. 1900-01-31) but settings
can change, for example, the separator and the date order to display dates in
'mm/dd/yyyy' format (e.g. 01/31/1900).

[1]: https://travis-ci.org/Islandora/controlled_access_terms.png?branch=8.x-1.x
[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-GPLv2-blue.svg?style=flat-square

## Documentation

Further documentation for this module is available on the [Islandora 8 documentation site](https://islandora.github.io/documentation/).

## Troubleshooting/Issues

Having problems or solved a problem? Check out the Islandora google groups for a solution.

* [Islandora Group](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora)
* [Islandora Dev Group](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora-dev)

## Maintainers/Sponsors

Current maintainers:

* [Seth Shaw](https://github.com/seth-shaw-unlv)

## Development

If you would like to contribute, please get involved by attending our weekly [Tech Call](https://github.com/Islandora/documentation/wiki#islandora-8-tech-calls). We love to hear from you!

If you would like to contribute code to the project, you need to be covered by an Islandora Foundation [Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_cla.pdf) or [Corporate Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_ccla.pdf). Please see the [Contributors](http://islandora.ca/resources/contributors) pages on Islandora.ca for more information.

We recommend using the [islandora-playbook](https://github.com/Islandora-Devops/islandora-playbook) to get started.

## License

[GPLv2](./LICENSE).
