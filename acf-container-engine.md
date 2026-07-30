# ACF Engine - Container Architecture

## Overview

Daripada membedakan **Group**, **Repeater**, dan **Flexible Content**
sebagai tiga jenis field yang berbeda, engine hanya mengenal satu konsep
inti:

> **Container**

Container adalah field yang dapat memiliki child field.

Perilaku Container ditentukan oleh **Mode**.

------------------------------------------------------------------------

## Mode

### 1. Single

Satu kumpulan field.

Contoh:

-   Hero
-   CTA
-   SEO
-   Button

```{=html}
<!-- -->
```
    Hero
    ├── Title
    ├── Description
    └── Image

Mirip ACF Group.

------------------------------------------------------------------------

### 2. Multiple

Sekumpulan field yang dapat diulang.

    Features
    ├── Item
    │   ├── Icon
    │   ├── Title
    │   └── Description

Mirip ACF Repeater.

------------------------------------------------------------------------

### 3. Flexible

Sekumpulan block dengan tipe berbeda.

    Content Builder
    ├── Hero
    ├── Gallery
    ├── CTA
    ├── FAQ
    └── Pricing

Mirip ACF Flexible Content.

------------------------------------------------------------------------

## Hierarki

    Page
    └── Section
        └── Container
            ├── Container
            ├── Field
            ├── Field
            └── Container

Container dapat berisi Container lain tanpa batas.

------------------------------------------------------------------------

## Field Types

-   Text
-   Textarea
-   Rich Editor
-   Number
-   Email
-   URL
-   Date
-   Time
-   Boolean
-   Select
-   Radio
-   Checkbox
-   Image
-   Gallery
-   File
-   Video
-   Icon
-   Color
-   Relationship
-   Container

------------------------------------------------------------------------

## Container Properties

  Property    Keterangan
  ----------- ------------------------------
  name        Nama internal
  label       Label UI
  mode        single / multiple / flexible
  min         Minimum item
  max         Maximum item
  collapsed   Tampilan ringkas
  sortable    Drag & drop
  cloneable   Bisa diduplikasi
  fields      Child field
  layouts     Layout flexible

------------------------------------------------------------------------

## Flexible Layout

    Content Builder
    ├── Hero
    │   ├── Title
    │   ├── Subtitle
    │   └── Image
    │
    ├── Gallery
    │   ├── Images
    │   └── Caption
    │
    └── CTA
        ├── Title
        ├── Description
        └── Button

------------------------------------------------------------------------

## Nested Example

    Pricing
    └── Plans (Multiple)
        ├── Plan Name
        ├── Price
        └── Features (Multiple)
            ├── Title
            └── Description

Tidak ada batas nesting.

------------------------------------------------------------------------

## JSON Example

``` json
{
  "plans": [
    {
      "name": "Basic",
      "price": 100000,
      "features": [
        {
          "title": "Unlimited Users",
          "description": "No limit"
        }
      ]
    }
  ]
}
```

------------------------------------------------------------------------

## Rendering Flow

    Blueprint
        ↓
    Container Tree
        ↓
    Dynamic Form
        ↓
    Validation
        ↓
    Save JSON
        ↓
    Blade Renderer

------------------------------------------------------------------------

## Philosophy

Engine hanya mengenal dua objek:

-   **Field**
-   **Container**

Semua konsep lain hanyalah konfigurasi:

-   Group = Container(mode=single)
-   Repeater = Container(mode=multiple)
-   Flexible Content = Container(mode=flexible)

Dengan pendekatan ini engine menjadi lebih sederhana, mudah dipelihara,
dan mendukung nested structure tanpa batas.
