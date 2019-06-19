# Controlled Access Terms

[![Build Status][1]](https://travis-ci.com/Islandora-CLAW/controlled_access_terms)
[![Contribution Guidelines][2]](./CONTRIBUTING.md)
[![LICENSE][3]](./LICENSE)

This Drupal 8 module creates bundles to represent common named entities
in archival description (Corporate Bodies, Families, and Persons) as well as
subject terms.

It is intended to be used in conjunction with both the [ArchivesSpace/Drupal 8
Integration project](https://github.com/jasloe/archivesspace-drupal) and
[Islandora 8](https://github.com/Islandora-CLAW/CLAW).

This module is under active development and will be in flux although master
should always work (theoretically). There are some field naming inconsistencies
that will be cleaned up along the way.

Feel free to add issues or post pull requests. Feedback and suggestions are
greatly appreciated.

## Content Types

Below is a list of the (at least partially) implemented content types with
their fields. The fields with "EDTF" accept and display dates corresponding
to the Library of Congress 2018 Extended Date/Time Format Specification (EDTF).
See the section below for more information on EDTF.

- Corporate Body
  - Preferred Name (Title)
  - Alternate Name
  - Founding Date (EDTF)
  - Dissolution Date (EDTF)
  - Parent Organization
  - Authorities
  - Description
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
- Family
  - Display Label (Title)
  - Authorities
  - Relation
  - Date Begin (EDTF)
  - Date End (EDTF)
- Person
  - Title/Display Name
  - Alternate Name
  - Preferred Name
  - Birth Date (EDTF)
  - Death Date (EDTF)
  - Relation
  - Authorities
  - Description
  - Member Of (Family or Corporate Body)
- Geographic Location
  - Name (Title)
  - Alternate Name
  - Authorities
  - Geographic Location ([WGS 84](https://en.wikipedia.org/wiki/World_Geodetic_System))
  - Broader
- Subject
  - Title
  - Body
  - Authorities
  - Type
    - Topical (mads:Topic)
    - Cultural Context
    - Genre/Form (mads:GenreForm)
    - Occupation (mads:Occupation)
    - Style/Period

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

[1]: https://travis-ci.org/Islandora-CLAW/controlled_access_terms.png?branch=8.x-1.x
[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-GPLv2-blue.svg?style=flat-square
