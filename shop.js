(() => {
    'use strict';

    const C = window.CMB;
    const root = document.querySelector('[data-products]');
    const categories = document.querySelector('[data-categories]');
    const search = document.querySelector('[data-product-search]');
    const sort = document.querySelector('[data-product-sort]');
    const resultCount = document.querySelector('[data-result-count]');
    if (!C || !root) return;

    let selectedCategory = '';
    let allProducts = [];

    function renderCategories(list) {
        if (!categories) return;
        categories.replaceChildren();
        const options = [{ name: 'All pieces', value: '' }, ...[...new Set(list.map(p => p.category).filter(Boolean))]
            .map(name => ({ name, value: name }))];
        options.forEach(option => {
            const button = C.el('button', 'category-chip' + (option.value === selectedCategory ? ' active' : ''), option.name);
            button.type = 'button';
            button.addEventListener('click', () => {
                selectedCategory = option.value;
                renderCategories(list);
                loadProducts();
            });
            categories.append(button);
        });
    }

    function sortedProducts(products) {
        const items = [...products];
        if (sort?.value === 'price-asc') items.sort((a, b) => Number(a.price) - Number(b.price));
        if (sort?.value === 'price-desc') items.sort((a, b) => Number(b.price) - Number(a.price));
        if (sort?.value === 'name-asc') items.sort((a, b) => a.name.localeCompare(b.name));
        return items;
    }

    function productCard(product) {
        const column = C.el('div', 'product-grid-item');
        const card = C.el('article', 'product-card');
        const link = C.el('a', 'product-card-link');
        link.href = 'product.html?id=' + encodeURIComponent(product.id);

        const media = C.el('div', 'product-media');
        if (product.image) {
            const image = document.createElement('img');
            image.src = C.imagePath(product.image);
            image.alt = product.name;
            image.loading = 'lazy';
            image.width = 800;
            image.height = 1000;
            media.append(image);
        } else {
            media.append(C.el('div', 'product-fallback', "Chi's Mini Boutique"));
        }

        const stock = Number(product.stock) || 0;
        if (stock < 1) media.append(C.el('span', 'sold-label', 'Sold out'));
        const caption = C.el('div', 'product-caption');
        caption.append(C.el('span', '', product.name), C.el('span', 'price', C.money(product.price)));
        link.append(media, caption);

        const actions = C.el('div', 'quick-add-row');
        const sizes = String(product.sizes || '').split(',').map(size => size.trim()).filter(Boolean);
        if (stock > 0) {
            let sizeSelect = null;
            if (sizes.length) {
                sizeSelect = document.createElement('select');
                sizeSelect.className = 'quick-add-size';
                sizeSelect.setAttribute('aria-label', 'Choose a size for ' + product.name);
                sizeSelect.add(new Option('Size', ''));
                sizes.forEach(size => sizeSelect.add(new Option(size, size)));
                actions.append(sizeSelect);
            }

            const button = C.el('button', 'quick-add-button', 'Add to bag');
            button.type = 'button';
            button.disabled = !!sizeSelect;
            button.addEventListener('click', () => {
                if (sizeSelect && !sizeSelect.value) {
                    sizeSelect.focus();
                    return;
                }
                C.state.products = [...C.state.products.filter(item => Number(item.id) !== Number(product.id)), {
                    id: Number(product.id), name: product.name, price: product.price,
                    image: product.image, stock: product.stock, category: product.category
                }];
                C.add(product.id, sizeSelect ? sizeSelect.value : '', 1);
                button.textContent = 'Added';
                window.setTimeout(() => { button.textContent = 'Add to bag'; }, 1100);
            });
            if (sizeSelect) sizeSelect.addEventListener('change', () => { button.disabled = !sizeSelect.value; });
            actions.append(button);
        } else {
            actions.append(C.el('span', 'quick-add-unavailable', 'Currently unavailable'));
        }

        card.append(link, actions);
        column.append(card);
        return column;
    }

    function renderProducts() {
        const query = (search?.value || '').trim().toLocaleLowerCase();
        const filtered = allProducts.filter(product => {
            const terms = [product.name, product.category, product.description].join(' ').toLocaleLowerCase();
            return !query || terms.includes(query);
        });
        const products = sortedProducts(filtered);
        root.replaceChildren();
        if (resultCount) resultCount.textContent = products.length + (products.length === 1 ? ' piece' : ' pieces');
        if (!products.length) {
            root.append(C.el('p', 'empty-state', query ? 'No pieces match your search. Try another name or category.' : 'The next edit is on its way.'));
            return;
        }
        products.forEach(product => root.append(productCard(product)));
    }

    async function loadProducts() {
        root.setAttribute('aria-busy', 'true');
        try {
            const url = new URL('api/products.php', document.baseURI);
            if (selectedCategory) url.searchParams.set('category', selectedCategory);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) throw new Error('The collection service returned ' + response.status + '.');
            const data = await response.json();
            if (!Array.isArray(data.products)) throw new Error('The collection response was incomplete.');

            allProducts = data.products;
            C.state.products = allProducts.map(product => ({
                id: Number(product.id), name: product.name, price: product.price,
                image: product.image, stock: product.stock, category: product.category
            }));
            renderCategories(Array.isArray(data.categories) ? data.categories : allProducts);
            renderProducts();
        } catch (error) {
            console.error('CMB collection loading failed:', error);
            root.replaceChildren(C.el('p', 'empty-state', 'The collection could not load. Please refresh the page or try again shortly.'));
            if (resultCount) resultCount.textContent = '';
        } finally {
            root.removeAttribute('aria-busy');
        }
    }

    search?.addEventListener('input', renderProducts);
    sort?.addEventListener('change', renderProducts);
    loadProducts();
})();
