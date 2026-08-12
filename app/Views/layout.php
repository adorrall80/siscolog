<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SisColog</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <div class="layout-controls" aria-label="Controles de vista">
        <button type="button" data-layout-toggle="top">Ocultar superior</button>
        <button type="button" data-layout-toggle="left">Ocultar izquierdo</button>
    </div>

    <div class="app-shell">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <?php require __DIR__ . '/partials/nav.php'; ?>

        <div class="workspace <?= ($hideRightSidebar ?? false) ? 'workspace-no-right' : '' ?>">
            <?php require __DIR__ . '/partials/sidebar-left.php'; ?>

            <main class="content-module">
                <?php require __DIR__ . '/partials/flash.php'; ?>
                <?= $content ?>
            </main>

            <?php if (($hideRightSidebar ?? false) === false): ?>
                <?php require __DIR__ . '/partials/sidebar-right.php'; ?>
            <?php endif; ?>
        </div>

        <?php require __DIR__ . '/partials/footer.php'; ?>
    </div>
    <script>
        (function () {
            const csrfToken = function () {
                const meta = document.querySelector('meta[name="csrf-token"]');

                return meta ? meta.getAttribute('content') || '' : '';
            };

            const applyCsrf = function (data) {
                const token = csrfToken();

                if (token !== '') {
                    data.set('_csrf', token);
                }

                return data;
            };

            window.siscologApplyCsrf = applyCsrf;
        })();

        (function () {
            const storageKey = 'siscolog-theme';
            const toggle = document.querySelector('[data-theme-toggle]');
            const storage = {
                get: function () {
                    try {
                        return window.localStorage.getItem(storageKey);
                    } catch (error) {
                        return null;
                    }
                },
                set: function (value) {
                    try {
                        window.localStorage.setItem(storageKey, value);
                    } catch (error) {
                        return;
                    }
                }
            };
            const applyTheme = function (theme) {
                const isDark = theme === 'dark';
                document.body.classList.toggle('theme-dark', isDark);
                if (toggle) {
                    toggle.textContent = isDark ? 'Modo claro' : 'Modo oscuro';
                }
            };

            applyTheme(storage.get() || 'light');

            if (toggle) {
                toggle.addEventListener('click', function () {
                    const nextTheme = document.body.classList.contains('theme-dark') ? 'light' : 'dark';
                    storage.set(nextTheme);
                    applyTheme(nextTheme);
                });
            }
        })();

        (function () {
            const keys = {
                top: 'siscolog-hide-top-menu',
                left: 'siscolog-hide-left-menu'
            };
            const buttons = document.querySelectorAll('[data-layout-toggle]');
            const storage = {
                get: function (key) {
                    try {
                        return window.localStorage.getItem(key);
                    } catch (error) {
                        return null;
                    }
                },
                set: function (key, value) {
                    try {
                        window.localStorage.setItem(key, value);
                    } catch (error) {
                        return;
                    }
                }
            };
            const applyLayout = function () {
                const topHidden = storage.get(keys.top) === '1';
                const leftHidden = storage.get(keys.left) === '1';

                document.body.classList.toggle('layout-no-top', topHidden);
                document.body.classList.toggle('layout-no-left', leftHidden);

                buttons.forEach(function (button) {
                    const target = button.getAttribute('data-layout-toggle');

                    if (target === 'top') {
                        button.textContent = topHidden ? 'Mostrar superior' : 'Ocultar superior';
                        button.classList.toggle('is-active', topHidden);
                    }

                    if (target === 'left') {
                        button.textContent = leftHidden ? 'Mostrar izquierdo' : 'Ocultar izquierdo';
                        button.classList.toggle('is-active', leftHidden);
                    }
                });
            };

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const target = button.getAttribute('data-layout-toggle');
                    const key = keys[target];

                    if (!key) {
                        return;
                    }

                    storage.set(key, storage.get(key) === '1' ? '0' : '1');
                    applyLayout();
                });
            });

            applyLayout();
        })();

        (function () {
            document.querySelectorAll('.toggle-switch input').forEach(function (input) {
                const label = input.closest('.toggle-switch');
                const text = label ? label.querySelector('strong') : null;
                const sync = function () {
                    if (text) {
                        text.textContent = input.checked ? 'Vigente' : 'No vigente';
                    }
                };

                input.addEventListener('change', sync);
                sync();
            });
        })();

        (function () {
            document.querySelectorAll('[data-existing-session-topics]').forEach(function (list) {
                list.addEventListener('click', function (event) {
                    const button = event.target.closest('.chip-remove');

                    if (!button) {
                        return;
                    }

                    const chip = button.closest('[data-existing-session-topic]');

                    if (chip) {
                        chip.remove();
                    }

                    if (!list.querySelector('[data-existing-session-topic]')) {
                        const empty = document.createElement('span');
                        empty.className = 'tag tone-gray';
                        empty.textContent = 'Sin temas registrados';
                        list.appendChild(empty);
                    }
                });
            });
        })();

        (function () {
            document.querySelectorAll('[data-existing-session-participants]').forEach(function (list) {
                list.addEventListener('click', function (event) {
                    const button = event.target.closest('.chip-remove');

                    if (!button) {
                        return;
                    }

                    const chip = button.closest('[data-existing-session-participant]');

                    if (chip) {
                        chip.remove();
                    }

                    if (!list.querySelector('[data-existing-session-participant]')) {
                        const empty = document.createElement('span');
                        empty.className = 'tag tone-gray';
                        empty.textContent = 'Sin participantes registrados';
                        list.appendChild(empty);
                    }
                });
            });
        })();

        (function () {
            document.querySelectorAll('[data-subtype-list]').forEach(function (list) {
                const form = list.closest('form');
                const search = form ? form.querySelector('[data-subtype-search]') : null;
                const selectedId = form ? form.querySelector('[data-subtype-selected-id]') : null;
                const selectedLabel = form ? form.querySelector('[data-subtype-selected-label]') : null;
                const newSubtypeName = form ? form.querySelector('[data-new-subtype-name]') : null;
                const options = Array.from(list.querySelectorAll('[data-subtype-option]'));

                if (!search || !selectedId) {
                    return;
                }

                const filterOptions = function () {
                    const needle = search.value.trim().toLowerCase();

                    options.forEach(function (option) {
                        const name = option.getAttribute('data-subtype-name') || '';
                        option.classList.toggle('is-hidden', needle !== '' && !name.includes(needle));
                    });
                };

                options.forEach(function (option) {
                    option.addEventListener('click', function () {
                        options.forEach(function (item) {
                            item.classList.remove('is-selected');
                        });
                        option.classList.add('is-selected');
                        selectedId.value = option.getAttribute('data-subtype-id') || '0';
                        search.value = option.getAttribute('data-subtype-label') || option.textContent.trim();

                        if (selectedLabel) {
                            selectedLabel.textContent = 'Seleccionado: ' + option.textContent.trim();
                        }

                        if (newSubtypeName) {
                            newSubtypeName.value = '';
                        }

                        if (form && form.hasAttribute('data-topic-subtype-form')) {
                            form.requestSubmit();
                        }
                    });
                });

                search.addEventListener('input', function () {
                    selectedId.value = '0';
                    options.forEach(function (item) {
                        item.classList.remove('is-selected');
                    });
                    if (selectedLabel) {
                        selectedLabel.textContent = 'Busca y selecciona uno existente. Si no esta, se agregara como nuevo subtipo.';
                    }
                    filterOptions();
                });

                filterOptions();
            });
        })();

        (function () {
            const form = document.querySelector('[data-topic-subtype-form]');
            const associatedPanel = document.querySelector('[data-associated-subtypes]');

            if (!form || !associatedPanel) {
                return;
            }

            const search = form.querySelector('[data-subtype-search]');
            const selectedId = form.querySelector('[data-subtype-selected-id]');
            const selectedLabel = form.querySelector('[data-subtype-selected-label]');
            const list = form.querySelector('[data-subtype-list]');

            const addAvailableOption = function (subtype) {
                if (!list || !subtype || !subtype.id || !subtype.name) {
                    return;
                }

                if (list.querySelector('[data-subtype-id="' + subtype.id + '"]')) {
                    return;
                }

                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'subtype-option is-available';
                option.setAttribute('data-subtype-option', '');
                option.setAttribute('data-subtype-id', subtype.id);
                option.setAttribute('data-subtype-name', subtype.name.toLowerCase());
                option.setAttribute('data-subtype-label', subtype.name);
                option.textContent = subtype.name;
                option.addEventListener('click', function () {
                    list.querySelectorAll('[data-subtype-option]').forEach(function (item) {
                        item.classList.remove('is-selected');
                    });
                    option.classList.add('is-selected');
                    if (selectedId) {
                        selectedId.value = subtype.id;
                    }
                    if (search) {
                        search.value = subtype.name;
                    }
                    if (selectedLabel) {
                        selectedLabel.textContent = 'Seleccionado: ' + subtype.name;
                    }
                });
                list.appendChild(option);
            };

            const removeAvailableOption = function (subtypeId) {
                if (!list || !subtypeId) {
                    return;
                }

                const option = list.querySelector('[data-subtype-id="' + subtypeId + '"]');

                if (option) {
                    option.remove();
                }
            };

            const addAssociatedChip = function (subtype, relation) {
                const chipList = associatedPanel.querySelector('.subtype-chip-list');

                if (!chipList || !subtype || !relation) {
                    return;
                }

                const empty = chipList.querySelector('[data-empty-associated]');

                if (empty) {
                    empty.remove();
                }

                if (chipList.querySelector('[data-relation-id="' + relation.id + '"]')) {
                    return;
                }

                const chip = document.createElement('span');
                chip.className = 'subtype-chip is-associated';
                chip.setAttribute('data-associated-subtype', '');
                chip.setAttribute('data-relation-id', relation.id);
                chip.setAttribute('data-subtype-id', subtype.id);
                chip.textContent = subtype.name + ' ';

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'chip-remove';
                button.setAttribute('data-remove-subtype-relation', '');
                button.setAttribute('data-remove-url', form.action + '/' + relation.id + '/quitar');
                button.setAttribute('aria-label', 'Quitar relacion ' + subtype.name);
                button.textContent = 'x';

                chip.appendChild(button);
                chipList.appendChild(chip);
            };

            const resetSubtypeForm = function () {
                if (selectedId) {
                    selectedId.value = '0';
                }
                if (search) {
                    search.value = '';
                }
                if (selectedLabel) {
                    selectedLabel.textContent = 'Busca y selecciona uno existente. Si no esta, se agregara como nuevo subtipo.';
                }
                if (list) {
                    list.querySelectorAll('[data-subtype-option]').forEach(function (item) {
                        item.classList.remove('is-selected', 'is-hidden');
                    });
                }
            };

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const data = new FormData(form);
                data.set('async', '1');
                window.siscologApplyCsrf(data);

                fetch(form.action, {
                    method: 'POST',
                    body: data
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (payload) {
                        if (!payload.ok) {
                            throw new Error(payload.message || 'No se pudo asociar el subtipo.');
                        }

                        addAssociatedChip(payload.subtype, payload.relation);
                        removeAvailableOption(String(payload.subtype.id));
                        resetSubtypeForm();
                    })
                    .catch(function (error) {
                        if (selectedLabel) {
                            selectedLabel.textContent = error.message;
                        }
                    });
            });

            associatedPanel.addEventListener('click', function (event) {
                const button = event.target.closest('[data-remove-subtype-relation]');

                if (!button) {
                    return;
                }

                const chip = button.closest('[data-associated-subtype]');
                const subtype = chip ? {
                    id: chip.getAttribute('data-subtype-id'),
                    name: chip.textContent.replace('x', '').trim()
                } : null;
                const removeUrl = button.getAttribute('data-remove-url');

                if (!removeUrl) {
                    return;
                }

                const data = new FormData();
                data.set('async', '1');
                window.siscologApplyCsrf(data);

                fetch(removeUrl, {
                    method: 'POST',
                    body: data
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (payload) {
                        if (!payload.ok) {
                            throw new Error(payload.message || 'No se pudo quitar la relacion.');
                        }

                        if (chip) {
                            chip.remove();
                        }

                        if (subtype) {
                            addAvailableOption(subtype);
                        }
                    })
                    .catch(function (error) {
                        if (selectedLabel) {
                            selectedLabel.textContent = error.message;
                        }
                    });
            });
        })();

        (function () {
            const typeInput = document.querySelector('[data-session-topic-type]');
            const subtypeInput = document.querySelector('[data-session-topic-subtype]');
            const subtypeList = document.querySelector('#session-topic-subtypes');
            const payload = document.querySelector('[data-session-topic-map]');
            const scopePayload = document.querySelector('[data-session-topic-scope-map]');
            const allPayload = document.querySelector('[data-session-topic-all-subtypes]');
            const help = document.querySelector('[data-session-topic-help]');
            const addButton = document.querySelector('[data-add-session-topic]');
            const clearButton = document.querySelector('[data-clear-session-topic]');
            const selectedList = document.querySelector('[data-session-topic-selected]');
            const hiddenContainer = document.querySelector('[data-session-topic-hidden]');
            let pendingConfirm = null;

            if (!typeInput || !subtypeInput || !subtypeList || !payload) {
                return;
            }

            let map = {};
            let scopeMap = {};
            let allSubtypes = [];

            try {
                map = JSON.parse(payload.textContent || '{}');
            } catch (error) {
                map = {};
            }

            try {
                scopeMap = JSON.parse(scopePayload ? scopePayload.textContent || '{}' : '{}');
            } catch (error) {
                scopeMap = {};
            }

            try {
                allSubtypes = JSON.parse(allPayload ? allPayload.textContent || '[]' : '[]');
            } catch (error) {
                allSubtypes = [];
            }

            const renderSubtypes = function () {
                const key = typeInput.value.trim().toLowerCase();
                const typeExists = key !== '' && Object.prototype.hasOwnProperty.call(map, key);
                const subtypes = typeExists ? map[key] : (key === '' ? [] : allSubtypes);

                subtypeList.innerHTML = '';
                subtypes.forEach(function (name) {
                    const option = document.createElement('option');
                    option.value = name;
                    option.label = (scopeMap[key] && scopeMap[key][name]) || '';
                    subtypeList.appendChild(option);
                });

                if (help) {
                    if (key === '') {
                        help.textContent = 'Elige o escribe un tipo para cargar sus subtipos relacionados.';
                    } else if (typeExists && subtypes.length > 0) {
                        help.textContent = 'Mostrando solo subtipos asociados a este tipo. Si escribes otro, se asociara al guardar.';
                    } else if (typeExists) {
                        help.textContent = 'Este tipo aun no tiene subtipos asociados. Escribe uno y quedara asociado al guardar.';
                    } else {
                        help.textContent = 'Tipo nuevo: mostrando todos los subtipos existentes para evitar duplicados. Si no calza ninguno, escribe uno nuevo.';
                    }
                }
            };

            typeInput.addEventListener('input', function () {
                subtypeInput.value = '';
                renderSubtypes();
            });

            const clearTopicSelection = function () {
                typeInput.value = '';
                subtypeInput.value = '';
                renderSubtypes();

                if (help) {
                    help.textContent = 'Selección limpia. Elige un tipo para cargar sus subtipos relacionados.';
                }

                typeInput.focus();
            };

            if (clearButton) {
                clearButton.addEventListener('click', clearTopicSelection);
            }

            if (addButton && selectedList && hiddenContainer) {
                let topicIndex = 0;

                const normalize = function (value) {
                    return value.trim().toLowerCase();
                };

                const existsTopic = function (typeName, subtypeName) {
                    const key = normalize(typeName) + '::' + normalize(subtypeName);

                    return Array.from(hiddenContainer.querySelectorAll('[data-topic-key]')).some(function (item) {
                        return item.getAttribute('data-topic-key') === key;
                    });
                };

                const relationExists = function (typeName, subtypeName) {
                    const typeKey = normalize(typeName);

                    if (!Object.prototype.hasOwnProperty.call(map, typeKey)) {
                        return false;
                    }

                    return (map[typeKey] || []).some(function (name) {
                        return normalize(name) === normalize(subtypeName);
                    });
                };

                const clearConfirmation = function () {
                    if (pendingConfirm) {
                        pendingConfirm.remove();
                        pendingConfirm = null;
                    }
                };

                const addHidden = function (typeName, subtypeName) {
                    const key = normalize(typeName) + '::' + normalize(subtypeName);
                    const wrapper = document.createElement('div');
                    wrapper.setAttribute('data-topic-key', key);
                    wrapper.setAttribute('data-topic-index', String(topicIndex));

                    const typeHidden = document.createElement('input');
                    typeHidden.type = 'hidden';
                    typeHidden.name = 'topic_pairs[' + topicIndex + '][type]';
                    typeHidden.value = typeName;

                    const subtypeHidden = document.createElement('input');
                    subtypeHidden.type = 'hidden';
                    subtypeHidden.name = 'topic_pairs[' + topicIndex + '][subtype]';
                    subtypeHidden.value = subtypeName;

                    wrapper.appendChild(typeHidden);
                    wrapper.appendChild(subtypeHidden);
                    hiddenContainer.appendChild(wrapper);
                    topicIndex += 1;

                    return key;
                };

                const addChip = function (typeName, subtypeName, key) {
                    const empty = selectedList.querySelector('[data-empty-session-topic]');

                    if (empty) {
                        empty.remove();
                    }

                    const chip = document.createElement('span');
                    chip.className = 'subtype-chip is-associated';
                    chip.setAttribute('data-session-topic-chip', '');
                    chip.setAttribute('data-topic-key', key);
                    chip.textContent = typeName + ': ' + subtypeName + ' ';

                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'chip-remove';
                    remove.setAttribute('aria-label', 'Quitar tema ' + typeName + ': ' + subtypeName);
                    remove.textContent = 'x';

                    chip.appendChild(remove);
                    selectedList.appendChild(chip);
                };

                const addTopic = function (typeName, subtypeName) {
                    const key = addHidden(typeName, subtypeName);
                    addChip(typeName, subtypeName, key);
                    typeInput.value = '';
                    subtypeInput.value = '';
                    renderSubtypes();

                    if (help) {
                        help.textContent = 'Tema agregado. Puedes agregar otro tipo-subtipo o guardar la sesion.';
                    }
                };

                const showRelationConfirmation = function (typeName, subtypeName) {
                    clearConfirmation();

                    const box = document.createElement('div');
                    box.className = 'relation-confirm';
                    box.setAttribute('role', 'alert');
                    box.innerHTML = ''
                        + '<span class="relation-confirm-icon" aria-hidden="true">?</span>'
                        + '<div class="relation-confirm-body">'
                        + '<strong>Confirmar nueva relacion</strong>'
                        + '<p>Quiere relacionar el tema <b></b> con el subtipo <b></b>?</p>'
                        + '<div class="actions-row">'
                        + '<button class="button small" type="button" data-confirm-topic-relation>Si, relacionar</button>'
                        + '<button class="button small secondary" type="button" data-cancel-topic-relation>Cancelar</button>'
                        + '</div>'
                        + '</div>';

                    const labels = box.querySelectorAll('b');
                    labels[0].textContent = typeName;
                    labels[1].textContent = subtypeName;

                    box.querySelector('[data-confirm-topic-relation]').addEventListener('click', function () {
                        addTopic(typeName, subtypeName);
                        clearConfirmation();
                    });

                    box.querySelector('[data-cancel-topic-relation]').addEventListener('click', function () {
                        clearConfirmation();

                        if (help) {
                            help.textContent = 'Relacion cancelada. Puedes elegir otro subtipo o escribir uno distinto.';
                        }
                    });

                    if (help) {
                        help.insertAdjacentElement('afterend', box);
                        help.textContent = 'Este par tipo-subtipo no estaba relacionado. Confirma antes de agregarlo.';
                    } else {
                        selectedList.insertAdjacentElement('afterend', box);
                    }

                    pendingConfirm = box;
                };

                addButton.addEventListener('click', function () {
                    const typeName = typeInput.value.trim();
                    const subtypeName = subtypeInput.value.trim();

                    if (typeName === '' || subtypeName === '') {
                        if (help) {
                            help.textContent = 'Debes indicar tipo y subtipo antes de agregarlo a la sesion.';
                        }
                        return;
                    }

                    if (existsTopic(typeName, subtypeName)) {
                        if (help) {
                            help.textContent = 'Ese tema ya esta agregado a la sesion.';
                        }
                        return;
                    }

                    if (!relationExists(typeName, subtypeName)) {
                        showRelationConfirmation(typeName, subtypeName);
                        return;
                    }

                    clearConfirmation();
                    addTopic(typeName, subtypeName);
                });

                selectedList.addEventListener('click', function (event) {
                    const button = event.target.closest('.chip-remove');

                    if (!button) {
                        return;
                    }

                    const chip = button.closest('[data-session-topic-chip]');
                    const key = chip ? chip.getAttribute('data-topic-key') : '';
                    const hidden = key
                        ? Array.from(hiddenContainer.querySelectorAll('[data-topic-key]')).find(function (item) {
                            return item.getAttribute('data-topic-key') === key;
                        })
                        : null;

                    if (chip) {
                        chip.remove();
                    }

                    if (hidden) {
                        hidden.remove();
                    }

                    if (!selectedList.querySelector('[data-session-topic-chip]')) {
                        const empty = document.createElement('span');
                        empty.className = 'tag tone-gray';
                        empty.setAttribute('data-empty-session-topic', '');
                        empty.textContent = 'Sin temas agregados todavia';
                        selectedList.appendChild(empty);
                    }
                });
            }

            renderSubtypes();
        })();

        (function () {
            const archivedToggle = document.querySelector('[data-family-archived-toggle]');
            const archivedPanel = document.querySelector('[data-family-archived-panel]');

            if (archivedToggle && archivedPanel) {
                const archivedLabel = archivedToggle.querySelector('[data-family-archived-toggle-label]');
                const archivedCount = archivedPanel.querySelectorAll('.family-archived-list article').length;

                archivedToggle.addEventListener('click', function () {
                    const willOpen = archivedToggle.getAttribute('aria-expanded') !== 'true';
                    archivedToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    archivedPanel.hidden = !willOpen;
                    if (archivedLabel) {
                        archivedLabel.textContent = (willOpen ? 'Ocultar archivados' : 'Mostrar archivados') + ' (' + archivedCount + ')';
                    }
                });
            }

            const maps = document.querySelectorAll('[data-family-map]');

            if (!maps.length) {
                return;
            }

            const setupMapSelection = function (map) {
                const form = document.querySelector('[data-family-relation-form]');
                const relationshipCounter = document.querySelector('[data-family-relationship-count]');
                const fitButton = document.querySelector('[data-family-fit]');
                const actualSizeButton = document.querySelector('[data-family-actual-size]');

                if (!form) {
                    return;
                }

                const fromInput = form.querySelector('[data-family-from-input]');
                const toInput = form.querySelector('[data-family-to-input]');
                const fromLabel = form.querySelector('[data-family-from-label]');
                const toLabel = form.querySelector('[data-family-to-label]');
                const saveButton = form.querySelector('[data-family-save]');
                const clearButton = form.querySelector('[data-family-clear]');
                const help = form.querySelector('[data-family-help]');
                const relationInput = form.querySelector('[data-family-relation-input]');
                const preview = form.querySelector('[data-family-relation-preview]');
                const previewFrom = form.querySelector('[data-family-preview-from]');
                const previewRelation = form.querySelector('[data-family-preview-relation]');
                const previewTo = form.querySelector('[data-family-preview-to]');
                const previewDirection = form.querySelector('[data-family-preview-direction]');
                const directionInputs = Array.from(form.querySelectorAll('[data-family-direction]'));
                const replaceConfirmedInput = form.querySelector('[data-family-replace-confirmed]');
                const replaceIdInput = form.querySelector('[data-family-replace-id]');
                const replaceWarning = form.querySelector('[data-family-replace-warning]');
                const currentRelation = form.querySelector('[data-family-current-relation]');
                const newRelation = form.querySelector('[data-family-new-relation]');
                const confirmReplace = form.querySelector('[data-family-confirm-replace]');
                const cancelReplace = form.querySelector('[data-family-cancel-replace]');
                const nodes = Array.from(map.querySelectorAll('[data-family-node]'));
                let fromKey = '';
                let toKey = '';
                let confirmedReplaceSignature = '';

                const showActualSize = function () {
                    map.classList.remove('is-fit');
                    map.style.removeProperty('--family-map-scale');
                    map.style.removeProperty('--family-map-fit-x');
                    map.style.removeProperty('--family-map-fit-y');
                    drawMap(map);
                };

                const showAll = function () {
                    showActualSize();
                    const canvas = map.querySelector('.family-node-canvas');
                    const content = map.querySelector('[data-family-map-content]');

                    if (!canvas || !content || !nodes.length) {
                        return;
                    }

                    const availableWidth = Math.max(1, map.clientWidth - 32);
                    const availableHeight = Math.max(1, map.clientHeight - 32);
                    const margin = 55;
                    const left = Math.min.apply(null, nodes.map(function (node) {
                        return node.offsetLeft - (node.offsetWidth / 2);
                    })) - margin;
                    const right = Math.max.apply(null, nodes.map(function (node) {
                        return node.offsetLeft + (node.offsetWidth / 2);
                    })) + margin;
                    const top = Math.min.apply(null, nodes.map(function (node) {
                        return node.offsetTop - (node.offsetHeight / 2);
                    })) - margin;
                    const bottom = Math.max.apply(null, nodes.map(function (node) {
                        return node.offsetTop + (node.offsetHeight / 2);
                    })) + margin;
                    const occupiedWidth = Math.max(1, right - left);
                    const occupiedHeight = Math.max(1, bottom - top);
                    const scale = Math.min(1.35, availableWidth / occupiedWidth, availableHeight / occupiedHeight);
                    const x = 16 + ((availableWidth - (occupiedWidth * scale)) / 2) - (left * scale);
                    const y = 16 + ((availableHeight - (occupiedHeight * scale)) / 2) - (top * scale);

                    map.style.setProperty('--family-map-scale', String(scale));
                    map.style.setProperty('--family-map-fit-x', x + 'px');
                    map.style.setProperty('--family-map-fit-y', y + 'px');
                    map.classList.add('is-fit');
                    map.scrollTo(0, 0);
                };

                const resetNodeStates = function () {
                    nodes.forEach(function (node) {
                        node.classList.remove('is-selected-from', 'is-selected-to');
                    });
                };

                const syncState = function () {
                    resetNodeStates();

                    if (fromKey) {
                        const from = nodes.find(function (node) {
                            return node.getAttribute('data-family-node') === fromKey;
                        });

                        if (from) {
                            from.classList.add('is-selected-from');
                        }
                    }

                    if (toKey) {
                        const to = nodes.find(function (node) {
                            return node.getAttribute('data-family-node') === toKey;
                        });

                        if (to) {
                            to.classList.add('is-selected-to');
                        }
                    }

                    if (fromInput) {
                        fromInput.value = fromKey;
                    }

                    if (toInput) {
                        toInput.value = toKey;
                    }

                    if (saveButton) {
                        saveButton.disabled = !(fromKey && toKey);
                    }
                };

                const nodeLabel = function (node) {
                    return node.getAttribute('data-family-node-label') || node.textContent.trim();
                };

                const selectedLabel = function (key) {
                    const node = nodes.find(function (item) {
                        return item.getAttribute('data-family-node') === key;
                    });

                    return node ? nodeLabel(node) : '';
                };

                const relationText = function () {
                    return relationInput ? relationInput.value.trim() : '';
                };

                const isBidirectional = function () {
                    const selected = directionInputs.find(function (input) {
                        return input.checked;
                    });

                    return Boolean(selected && selected.value === '1');
                };

                const relationTextFor = function (relation) {
                    return [
                        relation.getAttribute('data-from-label') || '',
                        relation.getAttribute('data-label') || '',
                        relation.getAttribute('data-to-label') || ''
                    ].filter(Boolean).join(' ');
                };

                const findExistingRelation = function () {
                    if (!fromKey || !toKey) {
                        return null;
                    }

                    const currentRelations = Array.from(map.querySelectorAll('[data-family-relation]'));

                    return currentRelations.find(function (relation) {
                        return relation.getAttribute('data-from') === fromKey
                            && relation.getAttribute('data-to') === toKey;
                    }) || null;
                };

                const replacementSignature = function (relation) {
                    return relation
                        ? (relation.getAttribute('data-id') || '') + '|' + relationText() + '|' + (isBidirectional() ? '1' : '0')
                        : '';
                };

                const prepareReplacementModal = function (existing) {
                    const fromText = selectedLabel(fromKey) || 'seleccione Persona A';
                    const toText = selectedLabel(toKey) || 'seleccione Persona B';

                    if (currentRelation && existing) {
                        currentRelation.textContent = relationTextFor(existing);
                        currentRelation.title = currentRelation.textContent;
                    }

                    if (newRelation && existing) {
                        newRelation.textContent = fromText + ' ' + (relationText() || 'escriba relacion') + ' ' + toText;
                        newRelation.title = newRelation.textContent;
                    }
                };

                const showReplacementModal = function (existing) {
                    prepareReplacementModal(existing);

                    if (replaceWarning) {
                        replaceWarning.hidden = false;
                    }

                    if (confirmReplace) {
                        confirmReplace.focus();
                    }
                };

                const hideReplacementModal = function () {
                    if (replaceWarning) {
                        replaceWarning.hidden = true;
                    }
                };

                const updateReplacementState = function () {
                    const existing = findExistingRelation();
                    const signature = replacementSignature(existing);
                    const isConfirmed = Boolean(existing && signature && confirmedReplaceSignature === signature);

                    if (replaceConfirmedInput) {
                        replaceConfirmedInput.value = isConfirmed ? '1' : '0';
                    }

                    if (replaceIdInput) {
                        replaceIdInput.value = isConfirmed && existing ? (existing.getAttribute('data-id') || '') : '';
                    }

                    prepareReplacementModal(existing);

                    if (!existing) {
                        confirmedReplaceSignature = '';
                        hideReplacementModal();
                    }

                    return Boolean(existing && !isConfirmed);
                };

                const updatePreview = function () {
                    const fromText = selectedLabel(fromKey);
                    const toText = selectedLabel(toKey);
                    const draftRelation = relationText();
                    const directionSymbol = isBidirectional() ? ' ↔ ' : ' → ';
                    const previewText = (fromText || 'Persona A')
                        + directionSymbol + (draftRelation || 'relacion')
                        + directionSymbol + (toText || 'Persona B');

                    if (previewFrom) {
                        previewFrom.textContent = fromText || 'seleccione Persona A';
                    }

                    if (previewRelation) {
                        previewRelation.textContent = draftRelation || 'escriba relacion';
                    }

                    if (previewTo) {
                        previewTo.textContent = toText || 'seleccione Persona B';
                    }

                    if (previewDirection) {
                        previewDirection.textContent = isBidirectional()
                            ? '\u25C0\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u25B6'
                            : '\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u25B6';
                    }

                    if (preview) {
                        preview.title = previewText;
                        preview.classList.toggle('is-ready', Boolean(fromText && toText && draftRelation));
                    }

                    if (help) {
                        if (!fromText) {
                            help.textContent = 'Selecciona la Persona A en el mapa.';
                        } else if (!toText) {
                            help.textContent = 'Persona A seleccionada. Ahora selecciona la Persona B.';
                        } else if (!draftRelation) {
                            help.textContent = 'Escribe la relación entre Persona A y Persona B.';
                        } else {
                            help.textContent = 'Todo listo. Presiona Unir participantes para guardar la relación.';
                        }
                    }

                    updateReplacementState();
                };

                const clearSelection = function () {
                    fromKey = '';
                    toKey = '';
                    confirmedReplaceSignature = '';

                    if (fromLabel) {
                        fromLabel.textContent = 'clic en una persona';
                    }

                    if (toLabel) {
                        toLabel.textContent = 'clic en otra persona';
                    }

                    if (relationInput) {
                        relationInput.value = '';
                    }

                    directionInputs.forEach(function (input) {
                        input.checked = input.value === '0';
                    });

                    if (help) {
                        help.textContent = 'Haz clic en dos personas del diagrama para armar la flecha. No necesitas usar listados.';
                    }

                    hideReplacementModal();
                    syncState();
                    updatePreview();
                };

                const pickNode = function (node) {
                    const key = node.getAttribute('data-family-node') || '';

                    if (key === '') {
                        return;
                    }

                    if (!fromKey || (fromKey && toKey)) {
                        fromKey = key;
                        toKey = '';
                        confirmedReplaceSignature = '';
                        if (fromLabel) {
                            fromLabel.textContent = nodeLabel(node);
                        }
                        if (toLabel) {
                            toLabel.textContent = 'clic en otra persona';
                        }
                        if (help) {
                            help.textContent = 'Persona A seleccionada. Ahora haz clic en la Persona B.';
                        }
                        syncState();
                        updatePreview();
                        return;
                    }

                    if (key === fromKey) {
                        if (help) {
                            help.textContent = 'La Persona B debe ser distinta de la Persona A.';
                        }
                        return;
                    }

                    toKey = key;
                    confirmedReplaceSignature = '';
                    if (toLabel) {
                        toLabel.textContent = nodeLabel(node);
                    }
                    if (help) {
                        help.textContent = 'Relacion lista. Escribe el tipo de vinculo y presiona Unir participantes.';
                    }
                    syncState();
                    updatePreview();
                };

                const savePosition = function (node, x, y) {
                    const data = new FormData();
                    data.set('node_key', node.getAttribute('data-family-node') || '');
                    data.set('x', String(x));
                    data.set('y', String(y));
                    window.siscologApplyCsrf(data);

                    fetch(form.action.replace('/family-relationships', '/family-node-position'), {
                        method: 'POST',
                        body: data
                    }).catch(function () {
                        if (help) {
                            help.textContent = 'Posicion actualizada en pantalla. No se pudo confirmar guardado en este momento.';
                        }
                    });
                };

                const updateRelationshipCounter = function () {
                    if (!relationshipCounter) {
                        return;
                    }

                    relationshipCounter.textContent = String(map.querySelectorAll('[data-family-relation]').length);
                };

                map.addEventListener('click', function (event) {
                    const remove = event.target.closest('[data-family-relation-remove]');

                    if (!remove) {
                        return;
                    }

                    event.preventDefault();

                    const relationId = remove.getAttribute('data-family-relation-id') || '';
                    const deleteUrl = remove.getAttribute('href') || '';
                    const relation = relationId
                        ? map.querySelector('[data-family-relation][data-id="' + relationId + '"]')
                        : null;

                    if (!deleteUrl || !relation) {
                        return;
                    }

                    const data = new FormData();
                    data.set('async', '1');
                    window.siscologApplyCsrf(data);

                    fetch(deleteUrl, {
                        method: 'POST',
                        body: data
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (payload) {
                            if (!payload.ok) {
                                throw new Error(payload.message || 'No se pudo quitar el vinculo.');
                            }

                            relation.remove();
                            drawMap(map);
                            updateRelationshipCounter();
                            updateReplacementState();

                            if (help) {
                                help.textContent = 'Vinculo quitado del diagrama.';
                            }
                        })
                        .catch(function (error) {
                            if (help) {
                                help.textContent = error.message;
                            }
                        });
                });

                const moveNode = function (node, x, y) {
                    node.style.left = x + '%';
                    node.style.top = y + '%';
                    node.setAttribute('data-family-x', String(x));
                    node.setAttribute('data-family-y', String(y));
                    drawMap(map);
                };

                nodes.forEach(function (node) {
                    let dragging = false;
                    let moved = false;
                    let startX = 0;
                    let startY = 0;

                    node.addEventListener('pointerdown', function (event) {
                        if (event.target.closest('.family-node-remove')) {
                            return;
                        }

                        dragging = true;
                        moved = false;
                        startX = event.clientX;
                        startY = event.clientY;
                        node.setPointerCapture(event.pointerId);
                    });

                    node.addEventListener('pointermove', function (event) {
                        if (!dragging) {
                            return;
                        }

                        const canvas = map.querySelector('.family-node-canvas');
                        const canvasRect = canvas ? canvas.getBoundingClientRect() : map.getBoundingClientRect();
                        const dx = Math.abs(event.clientX - startX);
                        const dy = Math.abs(event.clientY - startY);
                        moved = moved || dx > 4 || dy > 4;

                        if (!moved) {
                            return;
                        }

                        const horizontalMargin = Math.max(1.5, ((node.offsetWidth / 2 + 6) / canvas.offsetWidth) * 100);
                        const verticalMargin = Math.max(2.5, ((node.offsetHeight / 2 + 6) / canvas.offsetHeight) * 100);
                        const x = Math.max(horizontalMargin, Math.min(100 - horizontalMargin, ((event.clientX - canvasRect.left) / canvasRect.width) * 100));
                        const y = Math.max(verticalMargin, Math.min(100 - verticalMargin, ((event.clientY - canvasRect.top) / canvasRect.height) * 100));
                        moveNode(node, Math.round(x * 100) / 100, Math.round(y * 100) / 100);
                    });

                    node.addEventListener('pointerup', function (event) {
                        if (!dragging) {
                            return;
                        }

                        dragging = false;
                        node.releasePointerCapture(event.pointerId);

                        if (moved) {
                            savePosition(
                                node,
                                Number(node.getAttribute('data-family-x') || '50'),
                                Number(node.getAttribute('data-family-y') || '50')
                            );
                            if (help) {
                                help.textContent = 'Posicion guardada. Puedes seguir moviendo personas o crear una relacion.';
                            }
                        }
                    });

                    node.addEventListener('click', function (event) {
                        if (event.target.closest('.family-node-remove')) {
                            return;
                        }

                        if (moved) {
                            moved = false;
                            return;
                        }

                        pickNode(node);
                    });

                    node.addEventListener('keydown', function (event) {
                        if (event.key !== 'Enter' && event.key !== ' ') {
                            return;
                        }

                        event.preventDefault();
                        pickNode(node);
                    });
                });

                if (clearButton) {
                    clearButton.addEventListener('click', clearSelection);
                }

                form.addEventListener('submit', function (event) {
                    const existing = findExistingRelation();
                    const hasConfirmedReplacement = existing
                        && confirmedReplaceSignature === replacementSignature(existing)
                        && relationText() !== '';

                    if (relationText() === '') {
                        event.preventDefault();

                        if (help) {
                            help.textContent = 'Debes escribir la relacion antes de unir participantes.';
                        }

                        if (relationInput) {
                            relationInput.focus();
                            relationInput.reportValidity();
                        }

                        return;
                    }

                    if (existing && !hasConfirmedReplacement) {
                        event.preventDefault();
                        updateReplacementState();
                        showReplacementModal(existing);

                        if (help) {
                            help.textContent = 'Ya existe una relacion entre estas personas. Confirma si deseas reemplazarla.';
                        }
                    }
                });

                if (relationInput) {
                    relationInput.addEventListener('input', function () {
                        confirmedReplaceSignature = '';
                        hideReplacementModal();
                        updatePreview();
                    });
                    relationInput.addEventListener('change', function () {
                        confirmedReplaceSignature = '';
                        hideReplacementModal();
                        updatePreview();
                    });
                }

                if (fitButton) {
                    fitButton.addEventListener('click', showAll);
                }

                if (actualSizeButton) {
                    actualSizeButton.addEventListener('click', showActualSize);
                }

                directionInputs.forEach(function (input) {
                    input.addEventListener('change', function () {
                        confirmedReplaceSignature = '';
                        hideReplacementModal();
                        updatePreview();
                    });
                });

                if (confirmReplace) {
                    confirmReplace.addEventListener('click', function () {
                        const existing = findExistingRelation();

                        if (!existing || relationText() === '') {
                            if (help) {
                                help.textContent = 'Para reemplazar, primero escribe la nueva relacion.';
                            }
                            return;
                        }

                        confirmedReplaceSignature = replacementSignature(existing);
                        updateReplacementState();

                        if (help) {
                            help.textContent = 'Reemplazo confirmado. Guardando relacion actualizada.';
                        }

                        if (form.requestSubmit && saveButton) {
                            form.requestSubmit(saveButton);
                        } else {
                            form.submit();
                        }
                    });
                }

                if (cancelReplace) {
                    cancelReplace.addEventListener('click', function () {
                        confirmedReplaceSignature = '';
                        hideReplacementModal();

                        if (help) {
                            help.textContent = 'Reemplazo cancelado. Puedes cambiar Persona A, Persona B o la relacion.';
                        }

                        updatePreview();
                    });
                }

                clearSelection();
            };

            const drawMap = function (map) {
                const svg = map.querySelector('[data-family-lines]');
                const relations = Array.from(map.querySelectorAll('[data-family-relation]'));

                if (!svg) {
                    return;
                }

                map.querySelectorAll('[data-family-relation-badge]').forEach(function (badge) {
                    badge.remove();
                });

                svg.innerHTML = ''
                    + '<defs>'
                    + '<marker id="family-arrow" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto-start-reverse" markerUnits="strokeWidth">'
                    + '<path d="M0,0 L0,6 L9,3 z" fill="#1c5da1"></path>'
                    + '</marker>'
                    + '</defs>';

                const mapRect = map.getBoundingClientRect();

                relations.forEach(function (relation) {
                    const from = map.querySelector('[data-family-node="' + relation.getAttribute('data-from') + '"]');
                    const to = map.querySelector('[data-family-node="' + relation.getAttribute('data-to') + '"]');

                    if (!from || !to) {
                        return;
                    }

                    const canvas = map.querySelector('.family-node-canvas');
                    const canvasOffsetX = canvas ? canvas.offsetLeft : 0;
                    const canvasOffsetY = canvas ? canvas.offsetTop : 0;
                    const fromCenterX = canvasOffsetX + from.offsetLeft;
                    const fromCenterY = canvasOffsetY + from.offsetTop;
                    const toCenterX = canvasOffsetX + to.offsetLeft;
                    const toCenterY = canvasOffsetY + to.offsetTop;
                    const deltaX = toCenterX - fromCenterX;
                    const deltaY = toCenterY - fromCenterY;
                    const distance = Math.max(1, Math.hypot(deltaX, deltaY));
                    const unitX = deltaX / distance;
                    const unitY = deltaY / distance;
                    const fromRadius = Math.min(from.offsetWidth, from.offsetHeight) / 2 + 3;
                    const toRadius = Math.min(to.offsetWidth, to.offsetHeight) / 2 + 7;
                    const x1 = fromCenterX + unitX * fromRadius;
                    const y1 = fromCenterY + unitY * fromRadius;
                    const x2 = toCenterX - unitX * toRadius;
                    const y2 = toCenterY - unitY * toRadius;
                    const mx = (fromCenterX + toCenterX) / 2;
                    const my = (fromCenterY + toCenterY) / 2;

                    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    line.setAttribute('x1', String(x1));
                    line.setAttribute('y1', String(y1));
                    line.setAttribute('x2', String(x2));
                    line.setAttribute('y2', String(y2));
                    line.setAttribute('stroke', '#1c5da1');
                    line.setAttribute('stroke-width', '2');
                    line.setAttribute('stroke-linecap', 'round');
                    line.setAttribute('marker-end', 'url(#family-arrow)');
                    if (relation.getAttribute('data-bidirectional') === '1') {
                        line.setAttribute('marker-start', 'url(#family-arrow)');
                    }
                    svg.appendChild(line);

                    const badge = document.createElement('span');
                    badge.className = 'family-relation-badge';
                    badge.setAttribute('data-family-relation-badge', '');
                    badge.style.left = mx + 'px';
                    badge.style.top = my + 'px';
                    const fromLabel = relation.getAttribute('data-from-label') || '';
                    const relationLabel = relation.getAttribute('data-label') || '';
                    const toLabel = relation.getAttribute('data-to-label') || '';
                    const directionSymbol = relation.getAttribute('data-bidirectional') === '1' ? ' ↔ ' : ' → ';
                    const fullRelationshipLabel = fromLabel + ' — ' + relationLabel + directionSymbol + toLabel;
                    badge.title = fullRelationshipLabel;

                    const label = document.createElement('span');
                    label.textContent = fullRelationshipLabel;
                    label.title = badge.title;
                    badge.appendChild(label);

                    const deleteUrl = relation.getAttribute('data-delete-url') || '';

                    if (deleteUrl !== '') {
                        const remove = document.createElement('a');
                        remove.href = deleteUrl;
                        remove.className = 'family-relation-remove';
                        remove.setAttribute('data-family-relation-remove', '');
                        remove.setAttribute('data-family-relation-id', relation.getAttribute('data-id') || '');
                        remove.setAttribute('aria-label', 'Quitar vinculo ' + label.textContent);
                        remove.textContent = 'x';
                        badge.appendChild(remove);
                    }

                    const content = map.querySelector('[data-family-map-content]');
                    (content || map).appendChild(badge);
                });
            };

            maps.forEach(function (map) {
                setupMapSelection(map);
                drawMap(map);
            });
            window.addEventListener('resize', function () {
                maps.forEach(drawMap);
            });
        })();

        (function () {
            document.querySelectorAll('[data-ai-evolution-toggle]').forEach(function (toggleButton) {
                const analysisId = toggleButton.getAttribute('data-ai-evolution-toggle');
                const content = document.querySelector('[data-ai-evolution-content="' + analysisId + '"]');
                const closeButton = document.querySelector('[data-ai-evolution-close="' + analysisId + '"]');
                const label = toggleButton.querySelector('[data-ai-evolution-toggle-label]');

                if (!content) {
                    return;
                }

                const setOpen = function (isOpen) {
                    content.hidden = !isOpen;
                    toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    if (label) {
                        label.textContent = isOpen ? 'Ocultar evolución IA' : 'Abrir evolución IA';
                    }
                };

                toggleButton.addEventListener('click', function () {
                    setOpen(toggleButton.getAttribute('aria-expanded') !== 'true');
                });

                if (closeButton) {
                    closeButton.addEventListener('click', function () {
                        setOpen(false);
                        toggleButton.focus();
                    });
                }
            });

            document.querySelectorAll('[data-ai-prompt-open]').forEach(function (openButton) {
                const analysisId = openButton.getAttribute('data-ai-prompt-open');
                const modal = document.querySelector('[data-ai-prompt-modal="' + analysisId + '"]');

                if (!modal) {
                    return;
                }

                const close = function () {
                    modal.hidden = true;
                    document.body.classList.remove('modal-open');
                };

                openButton.addEventListener('click', function () {
                    modal.hidden = false;
                    document.body.classList.add('modal-open');
                });

                modal.querySelectorAll('[data-ai-prompt-close]').forEach(function (button) {
                    button.addEventListener('click', close);
                });

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        close();
                    }
                });

                const copyButton = modal.querySelector('[data-ai-prompt-copy]');
                const promptText = modal.querySelector('[data-ai-prompt-text]');
                const status = modal.querySelector('[data-ai-prompt-status]');

                if (copyButton && promptText) {
                    copyButton.addEventListener('click', async function () {
                        try {
                            await navigator.clipboard.writeText(promptText.value);
                            if (status) {
                                status.textContent = 'Prompt copiado.';
                            }
                        } catch (error) {
                            promptText.select();
                            if (status) {
                                status.textContent = 'Selecciona y copia el texto manualmente.';
                            }
                        }
                    });
                }
            });

            const setupAiModal = function (openSelector, modalAttribute, closeSelector) {
                document.querySelectorAll(openSelector).forEach(function (openButton) {
                    const analysisId = openButton.getAttribute(openSelector.slice(1, -1).split('=')[0]);
                    const modal = document.querySelector('[' + modalAttribute + '="' + analysisId + '"]');

                    if (!modal) {
                        return;
                    }

                    const close = function () {
                        modal.hidden = true;
                        document.body.classList.remove('modal-open');
                    };

                    openButton.addEventListener('click', function () {
                        modal.hidden = false;
                        document.body.classList.add('modal-open');
                    });
                    modal.querySelectorAll(closeSelector).forEach(function (button) {
                        button.addEventListener('click', close);
                    });
                    modal.addEventListener('click', function (event) {
                        if (event.target === modal) {
                            close();
                        }
                    });
                });
            };

            setupAiModal('[data-ai-edit-open]', 'data-ai-edit-modal', '[data-ai-edit-close]');
            setupAiModal('[data-ai-original-open]', 'data-ai-original-modal', '[data-ai-original-close]');

            document.querySelectorAll('[data-ai-review-form]').forEach(function (form) {
                const payload = form.querySelector('[data-ai-generated-text]');
                const finalText = form.querySelector('[data-ai-final-text]');
                let generatedText = '';

                try {
                    generatedText = JSON.parse(payload ? payload.textContent : '""');
                } catch (error) {
                    generatedText = '';
                }

                form.querySelectorAll('[data-ai-source]').forEach(function (source) {
                    source.addEventListener('change', function () {
                        if (!source.checked || !finalText) {
                            return;
                        }

                        if (source.value === 'ia') {
                            finalText.value = generatedText;
                        } else {
                            finalText.value = '';
                            finalText.focus();
                        }
                    });
                });
            });

            document.querySelectorAll('[data-ai-void-form]').forEach(function (form) {
                const modal = form.querySelector('[data-ai-void-modal]');
                const openButton = form.querySelector('[data-ai-void-open]');

                if (!modal || !openButton) {
                    return;
                }

                const close = function () {
                    modal.hidden = true;
                    document.body.classList.remove('modal-open');
                };

                openButton.addEventListener('click', function () {
                    modal.hidden = false;
                    document.body.classList.add('modal-open');
                });

                form.querySelectorAll('[data-ai-void-close]').forEach(function (button) {
                    button.addEventListener('click', close);
                });

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        close();
                    }
                });
            });
        })();

        (function () {
            const form = document.querySelector('[data-appointment-filter-form]');

            if (!form) {
                return;
            }

            const toggle = form.querySelector('[data-appointment-filter-toggle]');
            const panel = form.querySelector('[data-appointment-filter-panel]');
            const orderValue = form.querySelector('[data-appointment-order-value]');
            const viewValue = form.querySelector('[data-appointment-view-value]');

            if (toggle && panel) {
                toggle.addEventListener('click', function () {
                    const willOpen = panel.hidden;
                    panel.hidden = !willOpen;
                    toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });
            }

            form.querySelectorAll('[data-appointment-order]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (orderValue) {
                        orderValue.value = button.getAttribute('data-appointment-order') || 'desc';
                    }
                    form.submit();
                });
            });

            form.querySelectorAll('[data-appointment-view]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (viewValue) {
                        viewValue.value = button.getAttribute('data-appointment-view') || 'listado';
                    }
                    form.submit();
                });
            });
        })();

        (function () {
            const payload = document.querySelector('[data-linked-participants]');
            const select = document.querySelector('[data-linked-participant-select]');
            const addButton = document.querySelector('[data-add-linked-participant]');
            const selectedList = document.querySelector('[data-linked-participant-selected]');
            const hiddenContainer = document.querySelector('[data-linked-participant-hidden]');
            const help = document.querySelector('[data-linked-participant-help]');
            const modal = document.querySelector('[data-new-session-participant-modal]');
            const openModalButton = document.querySelector('[data-open-new-session-participant]');
            const closeModalButtons = document.querySelectorAll('[data-close-new-session-participant]');
            const confirmNewButton = document.querySelector('[data-confirm-new-session-participant]');
            const newTypeSelect = document.querySelector('[data-new-session-participant-type]');
            const newNameInput = document.querySelector('[data-new-session-participant-name]');
            const newParticipantError = document.querySelector('[data-new-session-participant-error]');
            const existingParticipants = document.querySelector('[data-existing-session-participants]');

            if (!payload || !select || !addButton || !selectedList || !hiddenContainer) {
                return;
            }

            let people = [];
            let participantIndex = 0;
            let temporaryParticipantIndex = 0;

            try {
                people = JSON.parse(payload.textContent || '[]');
            } catch (error) {
                people = [];
            }

            document.querySelectorAll('[data-existing-session-participant][data-linked-participant-id]').forEach(function (chip) {
                const option = select.querySelector('option[value="' + chip.getAttribute('data-linked-participant-id') + '"]');
                if (option) {
                    option.disabled = true;
                }
            });

            const personById = function (id) {
                return people.find(function (person) {
                    return String(person.id) === String(id);
                }) || null;
            };

            const addParticipant = function (person) {
                const participantKey = person.key || ('person-' + String(person.id));
                const wrapper = document.createElement('div');
                wrapper.setAttribute('data-linked-participant-key', participantKey);
                if (person.id) {
                    wrapper.setAttribute('data-linked-participant-id', String(person.id));
                }

                const fields = {
                    type: person.type,
                    text: person.name,
                    origin: 'O',
                    person_id: person.id || ''
                };

                Object.keys(fields).forEach(function (field) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'participant_rows[linked_' + participantIndex + '][' + field + ']';
                    input.value = fields[field];
                    wrapper.appendChild(input);
                });

                hiddenContainer.appendChild(wrapper);
                participantIndex += 1;

                const empty = selectedList.querySelector('[data-empty-linked-participant]');
                if (empty) {
                    empty.remove();
                }

                const chip = document.createElement('span');
                chip.className = 'subtype-chip is-associated';
                chip.setAttribute('data-linked-participant-chip', '');
                chip.setAttribute('data-linked-participant-key', participantKey);
                if (person.id) {
                    chip.setAttribute('data-linked-participant-id', String(person.id));
                }
                chip.textContent = person.label + ' ';

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'chip-remove';
                remove.setAttribute('aria-label', 'Quitar participante ' + person.label);
                remove.textContent = 'x';
                chip.appendChild(remove);
                selectedList.appendChild(chip);

                const option = person.id
                    ? select.querySelector('option[value="' + String(person.id) + '"]')
                    : null;
                if (option) {
                    option.disabled = true;
                }
            };

            addButton.addEventListener('click', function () {
                const person = personById(select.value);

                if (!person) {
                    if (help) {
                        help.textContent = 'Selecciona una persona vinculada antes de agregar.';
                    }
                    return;
                }

                if (document.querySelector('[data-linked-participant-id="' + String(person.id) + '"]')) {
                    if (help) {
                        help.textContent = 'Esa persona ya está agregada a la sesión.';
                    }
                    return;
                }

                addParticipant(person);
                select.value = '';

                if (help) {
                    help.textContent = 'Participante agregado. Puedes seleccionar otra persona vinculada.';
                }
            });

            selectedList.addEventListener('click', function (event) {
                const button = event.target.closest('.chip-remove');

                if (!button) {
                    return;
                }

                const chip = button.closest('[data-linked-participant-chip]');
                const participantKey = chip ? chip.getAttribute('data-linked-participant-key') : '';
                const personId = chip ? chip.getAttribute('data-linked-participant-id') : '';
                const hidden = hiddenContainer.querySelector('[data-linked-participant-key="' + participantKey + '"]');
                const option = personId ? select.querySelector('option[value="' + personId + '"]') : null;

                if (chip) {
                    chip.remove();
                }
                if (hidden) {
                    hidden.remove();
                }
                if (option) {
                    option.disabled = false;
                }

                if (!selectedList.querySelector('[data-linked-participant-chip]')) {
                    const empty = document.createElement('span');
                    empty.className = 'tag tone-gray';
                    empty.setAttribute('data-empty-linked-participant', '');
                    empty.textContent = 'Sin participantes adicionales';
                    selectedList.appendChild(empty);
                }
            });

            if (existingParticipants) {
                existingParticipants.addEventListener('click', function (event) {
                    const button = event.target.closest('.chip-remove');
                    const chip = button ? button.closest('[data-existing-session-participant]') : null;

                    if (!chip) {
                        return;
                    }

                    const personId = chip.getAttribute('data-linked-participant-id');
                    const option = personId ? select.querySelector('option[value="' + personId + '"]') : null;
                    chip.remove();

                    if (option) {
                        option.disabled = false;
                    }

                    if (!existingParticipants.querySelector('[data-existing-session-participant]')) {
                        const empty = document.createElement('span');
                        empty.className = 'tag tone-gray';
                        empty.textContent = 'Sin participantes registrados';
                        existingParticipants.appendChild(empty);
                    }
                });
            }

            const normalizeParticipantValue = function (value) {
                return String(value || '').trim().toLocaleLowerCase('es');
            };

            const closeNewParticipantModal = function () {
                if (!modal) {
                    return;
                }
                modal.hidden = true;
                document.body.classList.remove('modal-open');
            };

            const openNewParticipantModal = function () {
                if (!modal || !newTypeSelect || !newNameInput) {
                    return;
                }
                newTypeSelect.value = '';
                newNameInput.value = '';
                if (newParticipantError) {
                    newParticipantError.textContent = modal.getAttribute('data-save-message')
                        || 'La persona se guardará definitivamente al guardar.';
                }
                modal.hidden = false;
                document.body.classList.add('modal-open');
                newTypeSelect.focus();
            };

            if (openModalButton) {
                openModalButton.addEventListener('click', openNewParticipantModal);
            }

            closeModalButtons.forEach(function (button) {
                button.addEventListener('click', closeNewParticipantModal);
            });

            if (modal) {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeNewParticipantModal();
                    }
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal && !modal.hidden) {
                    closeNewParticipantModal();
                }
            });

            if (confirmNewButton && newTypeSelect && newNameInput) {
                confirmNewButton.addEventListener('click', function () {
                    const type = newTypeSelect.value.trim();
                    const name = newNameInput.value.trim();
                    const typeOption = newTypeSelect.options[newTypeSelect.selectedIndex];
                    const typeLabel = typeOption ? typeOption.textContent.trim() : type;
                    const duplicate = people.some(function (person) {
                        return normalizeParticipantValue(person.type) === normalizeParticipantValue(type)
                            && normalizeParticipantValue(person.name) === normalizeParticipantValue(name);
                    }) || Array.from(document.querySelectorAll('input[name^="participant_rows["][name$="[text]"]')).some(function (input) {
                        const wrapper = input.parentElement;
                        const typeInput = wrapper ? wrapper.querySelector('input[name$="[type]"]') : null;
                        return typeInput
                            && normalizeParticipantValue(typeInput.value) === normalizeParticipantValue(type)
                            && normalizeParticipantValue(input.value) === normalizeParticipantValue(name);
                    });

                    if (type === '' || name === '') {
                        if (newParticipantError) {
                            newParticipantError.textContent = 'Selecciona el tipo de persona y escribe su nombre.';
                        }
                        return;
                    }

                    if (duplicate) {
                        if (newParticipantError) {
                            newParticipantError.textContent = 'Esta persona ya existe o ya fue agregada a la sesión.';
                        }
                        return;
                    }

                    temporaryParticipantIndex += 1;
                    addParticipant({
                        id: null,
                        key: 'new-person-' + temporaryParticipantIndex,
                        type: type,
                        name: name,
                        label: typeLabel + ' — ' + name
                    });
                    if (help) {
                        help.textContent = name + ' se agregará, vinculará y aparecerá en el mapa cuando guardes la sesión.';
                    }
                    closeNewParticipantModal();
                });
            }
        })();

        (function () {
            const typeSelect = document.querySelector('[data-session-participant-type]');
            const textInput = document.querySelector('[data-session-participant-text]');
            const payload = document.querySelector('[data-session-participant-types]');
            const addButton = document.querySelector('[data-add-session-participant]');
            const currentList = document.querySelector('[data-existing-session-participants]');
            const selectedList = document.querySelector('[data-session-participant-selected]');
            const hiddenContainer = document.querySelector('[data-session-participant-hidden]');
            const help = document.querySelector('[data-session-participant-help]');

            if (!typeSelect || !textInput || !payload || !addButton || !selectedList || !hiddenContainer) {
                return;
            }

            let participantIndex = 0;
            let participantTypes = [];

            try {
                participantTypes = JSON.parse(payload.textContent || '[]');
            } catch (error) {
                participantTypes = [];
            }

            const typeNameFor = function (code) {
                const option = participantTypes.find(function (type) {
                    return type.code === code;
                });

                return option ? option.name : code;
            };

            const displayTextFor = function (typeCode, typedText) {
                const label = typeNameFor(typeCode).trim();
                const name = typedText.trim();

                if (typeCode === 'paciente') {
                    return label || 'Paciente';
                }

                if (typeCode === 'otro') {
                    return name !== '' ? name : label;
                }

                if (name === '') {
                    return label;
                }

                return normalize(name).startsWith(normalize(label) + ' ')
                    ? name
                    : label + ' ' + name;
            };

            const syncParticipantText = function () {
                const isPatient = typeSelect.value.trim() === 'paciente';

                textInput.disabled = isPatient;
                textInput.value = isPatient ? '' : textInput.value;
                textInput.placeholder = isPatient
                    ? 'Paciente se registra automaticamente'
                    : 'Ej: Juan Perez, abuelo materno';

                if (help && isPatient) {
                    help.textContent = 'Paciente no requiere nombre: se registra automaticamente como participante de la sesion.';
                }
            };

            const normalize = function (value) {
                return value.trim().toLowerCase();
            };

            const participantExists = function (text) {
                const key = normalize(text);

                if (currentList) {
                    const existing = Array.from(currentList.querySelectorAll('[data-participant-key]')).some(function (item) {
                        return item.getAttribute('data-participant-key') === key;
                    });

                    if (existing) {
                        return true;
                    }
                }

                return Array.from(hiddenContainer.querySelectorAll('[data-participant-key]')).some(function (item) {
                    return item.getAttribute('data-participant-key') === key;
                });
            };

            const addHidden = function (typeCode, text, origin) {
                const key = normalize(text);
                const wrapper = document.createElement('div');
                wrapper.setAttribute('data-participant-key', key);

                const fields = {
                    type: typeCode,
                    text: text,
                    origin: origin,
                    person_id: ''
                };

                Object.keys(fields).forEach(function (field) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'participant_rows[new_' + participantIndex + '][' + field + ']';
                    input.value = fields[field];
                    wrapper.appendChild(input);
                });

                hiddenContainer.appendChild(wrapper);
                participantIndex += 1;

                return key;
            };

            const addChip = function (typeCode, text, origin, key) {
                const empty = selectedList.querySelector('[data-empty-session-participant]');

                if (empty) {
                    empty.remove();
                }

                const chip = document.createElement('span');
                chip.className = 'subtype-chip is-associated';
                chip.setAttribute('data-session-participant-chip', '');
                chip.setAttribute('data-participant-key', key);
                chip.textContent = text + ' ';

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'chip-remove';
                remove.setAttribute('aria-label', 'Quitar participante ' + text);
                remove.textContent = 'x';

                chip.appendChild(remove);
                selectedList.appendChild(chip);
            };

            addButton.addEventListener('click', function () {
                let typeCode = typeSelect.value.trim();
                let text = textInput.value.trim();
                let origin = 'O';

                if (typeCode === '' && text === '') {
                    if (help) {
                        help.textContent = 'Debes seleccionar un tipo o escribir un participante antes de agregar.';
                    }
                    return;
                }

                if (typeCode === '') {
                    typeCode = 'otro';
                }

                if (typeCode === 'paciente') {
                    text = displayTextFor(typeCode, text);
                    origin = 'L';
                } else if (text === '') {
                    text = displayTextFor(typeCode, text);
                    origin = 'L';
                } else {
                    text = displayTextFor(typeCode, text);
                }

                if (participantExists(text)) {
                    if (help) {
                        help.textContent = 'Ese participante ya esta agregado a la cita.';
                    }
                    return;
                }

                const key = addHidden(typeCode, text, origin);
                addChip(typeCode, text, origin, key);
                typeSelect.value = '';
                textInput.value = '';

                if (help) {
                    help.textContent = 'Participante agregado. Puedes agregar otro o guardar la cita.';
                }
            });

            typeSelect.addEventListener('change', syncParticipantText);
            syncParticipantText();

            selectedList.addEventListener('click', function (event) {
                const button = event.target.closest('.chip-remove');

                if (!button) {
                    return;
                }

                const chip = button.closest('[data-session-participant-chip]');
                const key = chip ? chip.getAttribute('data-participant-key') : '';
                const hidden = key
                    ? Array.from(hiddenContainer.querySelectorAll('[data-participant-key]')).find(function (item) {
                        return item.getAttribute('data-participant-key') === key;
                    })
                    : null;

                if (chip) {
                    chip.remove();
                }

                if (hidden) {
                    hidden.remove();
                }

                if (!selectedList.querySelector('[data-session-participant-chip]')) {
                    const empty = document.createElement('span');
                    empty.className = 'tag tone-gray';
                    empty.setAttribute('data-empty-session-participant', '');
                    empty.textContent = 'Sin participantes nuevos agregados';
                    selectedList.appendChild(empty);
                }
            });
        })();
    </script>
</body>
</html>
