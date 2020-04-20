# Repeatable field group

Drupal 8 module that provides a field group formatter to group multi-value fields.

## Features

This module group the repeatable fields by value index into a single details container.

If the fields are set unlimited values, an add more fields will be use.

## Dependencies

- field_group

## Supported widgets

- Text field
- Text area
- Select (List)
- Autocomplete (No tag style!)
- Media library

## Not planning to support

The following fields does not work as a single field with unlimited values or does not fit the grouped layout of the category. Selecting this fields on the display would incur on unexpected behaviours.

- Autocomplete (tag style)
- Radio buttons
- Checkboxes
- Boolean

## Known issues

- Fields grouped must have the same cardinality.
- Mandatory field groups is recommended until we can set an empty value field in the middle of the unlimited field.
