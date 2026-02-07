# Template Syntax

Placeholders in Word templates use the `${...}` syntax. The package replaces them with values from your export class or model.

## Simple variables

```
${variableName}
```

The key must match a token key from your export (e.g. from `GlobalTokens::values()` or `itemTokens()`).

## Global variables

Set via `GlobalTokens::values()` or `GlobalVariables::setVariable()` / `setVariables()`:

```
${Date}
${Time}
${CompanyName}
```

## Blocks (loops)

Blocks repeat a section for each item in a collection:

```
${blockName}
    ${field1}, ${field2}
    ${nestedRelation.field}
${/blockName}
```

- `blockName` must match the name returned by `blockName()` (e.g. `TokensFromCollection`).
- Inside the block, use keys from `itemTokens($item)` (e.g. `field1`, `field2`).
- Nested relations use dot notation: `nestedRelation.field`.

Example template:

```
${customer}
    ${name}, ${email}
    ${deliveryAddress.street}, ${deliveryAddress.city} ${deliveryAddress.postcode}
${/customer}
```

## Relation variables

For Eloquent models (Exportable trait), access relations with dot notation:

```
${relationName.field}
${parent.child.field}
```

## Condition-based relation variables

When using the Exportable trait, you can reference a single related record by ID or by a condition:

**By ID** (single record):

```
${orders:15.product_id} ${orders:15.order_date}
```

**By condition** (field, operator, value):

```
${orders:product_id,=,4.product_id} ${orders:product_id,=,4.order_date}
```

Supported operators: `=`, `!=`, `>`, `<`, `>=`, `<=`. If no record matches, the value is null.

## Block naming conventions

- Block names must match between template and code (e.g. `blockName()` and `${blockName}...${/blockName}`).
- Use lowercase and underscores (e.g. `customer`, `order_items`).
- No spaces or special characters in block names.
