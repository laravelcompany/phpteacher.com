---
title: "Creating Custom Content Types in Drupal 9"
description: "Learn how to create custom content types programmatically in Drupal 9 with YAML configuration files. Covers fields, display modes, entity references, and taxonomy."
pubDate: "2022-08-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Drupal", "Drupal 9", "Content Types", "CMS", "PHP"]
selected: false
---

Drupal 9's configuration management system lets you define content types programmatically using YAML files. Instead of clicking through the web interface and exporting configuration, you create the files directly and enable your module. The result is portable, version-controllable, and repeatable.

This article walks through creating custom content types in Drupal 9 with fields, display modes, form displays, and entity references—all without touching the web interface.

## Why Build Programmatically?

Using the Drupal web interface to create content types works, but it has limitations:

- Configuration is tied to a single site
- Exporting and importing requires extra steps
- Changes are harder to track in version control

Building programmatically means your content type definition lives in a custom module. Deploy it to any site, and the content type arrives with it. Update the YAML files, run `drush cr`, and the changes apply.

## Module Structure

Create a custom module with the following structure:

```
d9_content_type/
├── d9_content_type.info.yml
└── config/
    └── install/
        ├── node.type.html_page.yml
        ├── field.storage.node.field_content.yml
        ├── field.field.node.html_page.field_content.yml
        ├── core.entity_form_display.node.html_page.default.yml
        ├── core.entity_view_display.node.html_page.default.yml
        ├── core.entity_view_mode.node.teaser.yml
        └── core.entity_form_mode.node.prova_form_display.yml
```

All configuration files go under `config/install/`. When the module is enabled, Drupal reads these files and creates the corresponding configuration entities.

## The Info File

Start with `d9_content_type.info.yml`:

```yaml
name: D9 Content Type
description: Demonstrates creating a content type programmatically
type: module
core_version_requirement: ^9 || ^10
package: Custom
```

The `core_version_requirement` ensures compatibility with Drupal 9 and 10. Install the module with Drush:

```bash
../vendor/drush/drush/drush pm:enable d9_content_type
```

## Defining the Content Type

`node.type.html_page.yml` defines the content type itself:

```yaml
langcode: en
status: true
dependencies:
  enforced:
    module:
      - d9_content_type
name: 'Html Page'
type: html_page
description: 'A content type for HTML pages with custom fields.'
help: ''
new_revision: false
preview_mode: 1
display_submitted: true
```

Key properties:

- **`name`**: Human-readable name shown in the admin UI
- **`type`**: Machine name used internally (lowercase, underscores)
- **`description`**: Explanation shown on the content type listing
- **`new_revision`**: Whether saving creates a new revision
- **`preview_mode`**: 0 = no preview, 1 = optional, 2 = required
- **`display_submitted`**: Show author and date on the node page

## Defining Fields

Fields need two configuration files: one for storage (defines the field globally) and one for the instance (attaches it to a specific content type).

### Field Storage

`field.storage.node.field_content.yml`:

```yaml
langcode: en
status: true
dependencies:
  module:
    - node
    - text
id: node.field_content
field_name: field_content
entity_type: node
type: text_long
settings: {  }
module: text
locked: false
cardinality: 1
translatable: true
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

This defines `field_content` as a `text_long` field on the `node` entity. Setting `locked: false` means Drupal won't prevent the field from being deleted. The `cardinality` of `1` means single-value only.

### Field Instance

`field.field.node.html_page.field_content.yml` attaches the storage to the `html_page` content type:

```yaml
langcode: en
status: true
dependencies:
  config:
    - field.storage.node.field_content
    - node.type.html_page
  module:
    - text
id: node.html_page.field_content
field_name: field_content
entity_type: node
bundle: html_page
label: Content
description: ''
required: false
translatable: false
default_value: {  }
default_value_callback: ''
settings: {  }
field_type: text_long
```

The `id` follows the pattern `node.{bundle}.{field_name}`. The `bundle` must match the content type machine name. `required: false` means the field is optional when creating content.

## Form Display Configuration

`core.entity_form_display.node.html_page.default.yml` configures how the node add/edit form appears:

```yaml
langcode: en
status: true
dependencies:
  config:
    - field.field.node.html_page.field_content
    - node.type.html_page
  module:
    - path
    - text
id: node.html_page.default
targetEntityType: node
bundle: html_page
mode: default
content:
  created:
    type: datetime_timestamp
    weight: 10
    region: content
    settings: {  }
    third_party_settings: {  }
  field_content:
    type: text_textarea
    weight: 121
    region: content
    settings:
      rows: 5
      placeholder: ''
    third_party_settings: {  }
  path:
    type: path
    weight: 30
    region: content
    settings: {  }
    third_party_settings: {  }
  promote:
    type: boolean_checkbox
    weight: 15
    region: content
    settings:
      display_label: true
    third_party_settings: {  }
  status:
    type: boolean_checkbox
    weight: 120
    region: content
    settings:
      display_label: true
    third_party_settings: {  }
  sticky:
    type: boolean_checkbox
    weight: 16
    region: content
    settings:
      display_label: true
    third_party_settings: {  }
  title:
    type: string_textfield
    weight: -5
    region: content
    settings:
      size: 60
      placeholder: ''
    third_party_settings: {  }
  uid:
    type: entity_reference_autocomplete
    weight: 5
    region: content
    settings:
      match_operator: CONTAINS
      match_limit: 10
      size: 60
      placeholder: ''
    third_party_settings: {  }
hidden:
  langcode: true
```

Each field in the `content` section specifies its widget type and display weight. The `hidden` section lists fields that should not appear on the form.

## View Display Configuration

`core.entity_view_display.node.html_page.default.yml` controls how the content appears when viewed:

```yaml
langcode: en
status: true
dependencies:
  config:
    - field.field.node.html_page.field_content
    - node.type.html_page
  module:
    - text
    - user
id: node.html_page.default
targetEntityType: node
bundle: html_page
mode: default
content:
  field_content:
    type: text_default
    label: above
    settings: {  }
    third_party_settings: {  }
    weight: 101
    region: content
  links:
    settings: {  }
    third_party_settings: {  }
    weight: 100
    region: content
hidden:
  langcode: true
```

The `field_content` uses the `text_default` formatter, which renders the full text without trimming. `label: above` places the field label above the value.

## Custom View Mode: Teaser

Create a teaser view mode in `core.entity_view_mode.node.teaser.yml`:

```yaml
langcode: en
status: true
dependencies:
  module:
    - node
id: node.teaser
label: Teaser
targetEntityType: node
cache: true
```

Then configure what fields appear in teaser mode with `core.entity_view_display.node.html_page.teaser.yml`:

```yaml
langcode: en
status: true
dependencies:
  config:
    - core.entity_view_mode.node.teaser
    - field.field.node.html_page.field_content
    - node.type.html_page
  module:
    - user
id: node.html_page.teaser
targetEntityType: node
bundle: html_page
mode: teaser
content:
  links:
    settings: {  }
    third_party_settings: {  }
    weight: 100
    region: content
hidden:
  field_content: true
  langcode: true
```

The teaser hides `field_content` and only shows links. You can create different view modes for different contexts—teasers for listing pages, cards for grid displays, full content for node pages.

## Custom Form Mode

Form modes let you create different versions of the create/edit form. Define the mode in `core.entity_form_mode.node.prova_form_display.yml`:

```yaml
langcode: en
status: true
dependencies:
  module:
    - node
id: node.prova_form_display
label: prova form display
targetEntityType: node
cache: true
```

After enabling the module, the new form mode appears at `/admin/structure/display-modes/form`. You can configure which fields appear in each form mode and how they are laid out.

## Adding Entity References

Entity reference fields connect content types to other entities. To add an entity reference field to the `html_page` content type, create an additional field storage and instance:

```yaml
# field.storage.node.field_related_page.yml
langcode: en
status: true
dependencies:
  module:
    - node
id: node.field_related_page
field_name: field_related_page
entity_type: node
type: entity_reference
settings:
  target_type: node
module: core
locked: false
cardinality: -1
translatable: true
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

The `target_type: node` means this field references any node type. `cardinality: -1` allows unlimited values.

Then attach it to the `html_page` bundle:

```yaml
# field.field.node.html_page.field_related_page.yml
langcode: en
status: true
dependencies:
  config:
    - field.storage.node.field_related_page
    - node.type.html_page
id: node.html_page.field_related_page
field_name: field_related_page
entity_type: node
bundle: html_page
label: 'Related Pages'
required: false
translatable: false
default_value: {  }
default_value_callback: ''
settings:
  handler: default
  handler_settings:
    target_bundles:
      html_page: html_page
field_type: entity_reference
```

The `handler_settings.target_bundles` restricts references to only `html_page` content types.

## Working With Taxonomy

Taxonomy references work the same way but target vocabulary entities:

```yaml
# field.storage.node.field_tags.yml
langcode: en
status: true
dependencies:
  module:
    - node
    - taxonomy
id: node.field_tags
field_name: field_tags
entity_type: node
type: entity_reference
settings:
  target_type: taxonomy_term
module: core
locked: false
cardinality: -1
translatable: true
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

The field instance references `tags` as the target vocabulary:

```yaml
# field.field.node.html_page.field_tags.yml
settings:
  handler: default
  handler_settings:
    target_bundles:
      tags: tags
field_type: entity_reference
```

## Conclusion

Creating content types programmatically in Drupal 9 gives you portable, version-controllable configuration that travels with your custom module. The pattern is the same for every content type:

1. Define the content type in `node.type.{machine_name}.yml`
2. Define field storage in `field.storage.node.{field_name}.yml`
3. Attach the field in `field.field.node.{bundle}.{field_name}.yml`
4. Configure the form display
5. Configure the view display
6. Add custom form and view modes as needed

This approach scales from simple content types with a single text field to complex structures with entity references, taxonomy, image fields, and paragraphs. The YAML structure is declarative and predictable. Once you understand the pattern, you can create any content type Drupal supports.
