<div class="field-group">
    <label for="name">Nama Barang</label>
    <input
        id="name"
        name="name"
        type="text"
        maxlength="150"
        value="<?= e($values['name']) ?>"
        autocomplete="off"
        required
        <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>
    >
    <?php if (isset($errors['name'])): ?>
        <p class="field-error" id="name-error"><?= e($errors['name']) ?></p>
    <?php endif; ?>
</div>

<div class="field-group">
    <label for="category_id">Kategori</label>
    <select
        id="category_id"
        name="category_id"
        required
        <?= isset($errors['category_id']) ? 'aria-invalid="true" aria-describedby="category-error"' : '' ?>
    >
        <option value="">Pilih kategori</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= e($category['id']) ?>"<?= (string) $category['id'] === $values['category_id'] ? ' selected' : '' ?>>
                <?= e($category['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($errors['category_id'])): ?>
        <p class="field-error" id="category-error"><?= e($errors['category_id']) ?></p>
    <?php endif; ?>
</div>

<div class="field-row">
    <div class="field-group">
        <label for="price">Harga</label>
        <div class="input-prefix">
            <span aria-hidden="true">Rp</span>
            <input
                id="price"
                name="price"
                type="number"
                min="0"
                max="9999999999.99"
                step="0.01"
                inputmode="decimal"
                value="<?= e($values['price']) ?>"
                required
                <?= isset($errors['price']) ? 'aria-invalid="true" aria-describedby="price-error"' : '' ?>
            >
        </div>
        <?php if (isset($errors['price'])): ?>
            <p class="field-error" id="price-error"><?= e($errors['price']) ?></p>
        <?php endif; ?>
    </div>

    <div class="field-group">
        <label for="stock">Stok</label>
        <input
            id="stock"
            name="stock"
            type="number"
            min="0"
            max="4294967295"
            step="1"
            inputmode="numeric"
            value="<?= e($values['stock']) ?>"
            required
            <?= isset($errors['stock']) ? 'aria-invalid="true" aria-describedby="stock-error"' : '' ?>
        >
        <?php if (isset($errors['stock'])): ?>
            <p class="field-error" id="stock-error"><?= e($errors['stock']) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="form-actions">
    <button class="button button-primary" type="submit"<?= $categories === [] ? ' disabled' : '' ?>>Simpan Barang</button>
    <a class="button button-secondary" href="index.php">Batal</a>
</div>
