# Controlled Access Terms

[![Build Status][1]](https://travis-ci.com/Islandora-CLAW/controlled_access_terms)
[![Contribution Guidelines][2]](./CONTRIBUTING.md)
[![LICENSE][3]](./LICENSE)

This Drupal 8 module creates bundles to represent common named entities
in archival description (Corporate Bodies, Families, and Persons) as well as
subject terms.

It is intended to be used in conjunction with both the [ArchivesSpace/Drupal 8
Integration project](https://github.com/jasloe/archivesspace-drupal) and
[Islandora CLAW](https://github.com/Islandora-CLAW/CLAW).

This module is under active development and will be in flux although master
should always work (theoretically). There are some field naming inconsistencies
that will be cleaned up along the way.

Feel free to add issues or post pull requests. Feedback and suggestions are
greatly appreciated.

## Content Types

Below is a list of the (at least partially) implemented content types with
their fields. *Emphasized fields* were originally planned but proved infeasible
given the structure of Drupal's RDF module and the Islandora JSON-LD module.

- Corporate Body
  - Preferred Name (Title)
  - Alternate Name
  - Founding Date (EDTF v.1)
  - Dissolution Date (EDTF v.1)
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
  - Date Begin (EDTF v.1)
  - Date End (EDTF v.1)
- Person
  - Title/Display Name
  - Alternate Name
  - Preferred Name
  - Birth Date (EDTF v.1)
  - Death Date (EDTF v.1)
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

[1]: https://travis-ci.org/Islandora-CLAW/controlled_access_terms.png?branch=8.x-1.x
[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-GPLv2-blue.svg?style=flat-square
