# User Taste Profile
- Communicates in Indonesian (Bahasa Indonesia). Confidence: 0.95
- Gives brief, high-level directional instructions and expects autonomous exploration and full implementation — does not spell out step-by-step. Confidence: 0.8
- References specific UI page URLs and file paths to anchor requests (e.g. pointing at a page URL + a model file). Confidence: 0.75
- Provides business domain context (e.g. "cold storage with meat, chicken, fish") to guide implementation decisions rather than dictating technical details. Confidence: 0.8
- Works on Windows with a Laravel PHP project (WMS — Warehouse Management System). Confidence: 0.9
- Expects realistic, domain-specific seed data rather than generic placeholders when asking for seeders. Confidence: 0.75
- Follows strict foreign key naming conventions: FK column = `{table}_id_{referenced_table}` (e.g. `po_id_supplier` referencing `supplier.supplier_id`), not just string columns. Will proactively correct the assistant when naming is wrong. Confidence: 0.85
- Is technically knowledgeable about Laravel/MySQL schema design (PKs, FKs, type matching, constraints) and expects the assistant to get these details right without needing correction. Confidence: 0.8
