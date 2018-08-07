# Controlled Access Terms

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
their fields. *Emphasized fields* are planned but have not yet been implemented.

- Corporate Body
  - Preferred Name (Title)
  - Alternate Name
  - Founding Date
  - Dissolution Date
  - Parent Organization
  - Authorities
  - Description
  - *Type* *Abandoned due to an inability to alter JSON-LD's @type attribute based on a field.*
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
  - Date Begin
  - Date End
- Person
  - (Title is auto generated from Preferred Name)
  - Alternate Name
  - Preferred Name
  - Birth Date (EDTF v.1)
  - Death Date (EDTF v.1)
  - Relation
  - Authorities
  - Description
  - *Member Of (Family or Corporate Body)*
- Geographic Location
  - Name (Title)
  - Alternate Name
  - Authorities
  - Geographic Location ([WGS 84](https://en.wikipedia.org/wiki/World_Geodetic_System))
  - *Broader*
- Subject
  - Title
  - Body
  - Authorities
  - *Type* *Abandoned due to an inability to alter JSON-LD's @type attribute based on a field.*
    - Topical (mads:Topic)
    - Cultural Context
    - Genre/Form (mads:GenreForm)
    - Occupation (mads:Occupation)
    - Style/Period
