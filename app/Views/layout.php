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
            const maps = document.querySelectorAll('[data-family-map]');

            if (!maps.length) {
                return;
            }

            const setupMapSelection = function (map) {
                const form = document.querySelector('[data-family-relation-form]');
                const relationshipCounter = document.querySelector('[data-family-relationship-count]');

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
                const replaceConfirmedInput = form.querySelector('[data-family-replace-confirmed]');
                const replaceIdInput = form.querySelector('[data-family-replace-id]');
                const replaceWarning = form.querySelector('[data-family-replace-warning]');
                const currentRelation = form.querySelector('[data-family-current-relation]');
                const newRelation = form.querySelector('[data-family-new-relation]');
                const confirmReplace = form.querySelector('[data-family-confirm-replace]');
                const cancelReplace = form.querySelector('[data-family-cancel-replace]');
                const nodes = Array.from(map.querySelectorAll('[data-family-node]'));
                const existingRelations = Array.from(map.querySelectorAll('[data-family-relation]'));
                let fromKey = '';
                let toKey = '';
                let confirmedReplaceSignature = '';

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

                    return existingRelations.find(function (relation) {
                        return relation.getAttribute('data-from') === fromKey
                            && relation.getAttribute('data-to') === toKey;
                    }) || null;
                };

                const replacementSignature = function (relation) {
                    return relation ? (relation.getAttribute('data-id') || '') + '|' + relationText() : '';
                };

                const prepareReplacementModal = function (existing) {
                    const fromText = selectedLabel(fromKey) || 'seleccione origen';
                    const toText = selectedLabel(toKey) || 'seleccione destino';

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
                    const previewText = 'Desde ' + (fromText || 'seleccione origen')
                        + ' | ' + (draftRelation || 'escriba relacion')
                        + ' | Hacia ' + (toText || 'seleccione destino');

                    if (previewFrom) {
                        previewFrom.textContent = fromText || 'seleccione origen';
                    }

                    if (previewRelation) {
                        previewRelation.textContent = draftRelation || 'escriba relacion';
                    }

                    if (previewTo) {
                        previewTo.textContent = toText || 'seleccione destino';
                    }

                    if (preview) {
                        preview.title = previewText;
                        preview.classList.toggle('is-ready', Boolean(fromText && toText && draftRelation));
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
                            help.textContent = 'Origen seleccionado. Ahora haz clic en la persona destino.';
                        }
                        syncState();
                        updatePreview();
                        return;
                    }

                    if (key === fromKey) {
                        if (help) {
                            help.textContent = 'El destino debe ser otra persona. Elige un nodo distinto.';
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

                        const mapRect = map.getBoundingClientRect();
                        const dx = Math.abs(event.clientX - startX);
                        const dy = Math.abs(event.clientY - startY);
                        moved = moved || dx > 4 || dy > 4;

                        if (!moved) {
                            return;
                        }

                        const x = Math.max(7, Math.min(93, ((event.clientX - mapRect.left) / mapRect.width) * 100));
                        const y = Math.max(11, Math.min(89, ((event.clientY - mapRect.top) / mapRect.height) * 100));
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

                    node.addEventListener('click', function () {
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
                            help.textContent = 'Reemplazo cancelado. Puedes cambiar origen, destino o relacion.';
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
                    + '<marker id="family-arrow" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto" markerUnits="strokeWidth">'
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

                    const fromRect = from.getBoundingClientRect();
                    const toRect = to.getBoundingClientRect();
                    const x1 = fromRect.left - mapRect.left + fromRect.width / 2;
                    const y1 = fromRect.top - mapRect.top + fromRect.height / 2;
                    const x2 = toRect.left - mapRect.left + toRect.width / 2;
                    const y2 = toRect.top - mapRect.top + toRect.height / 2;
                    const mx = (x1 + x2) / 2;
                    const my = (y1 + y2) / 2;

                    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    line.setAttribute('x1', String(x1));
                    line.setAttribute('y1', String(y1));
                    line.setAttribute('x2', String(x2));
                    line.setAttribute('y2', String(y2));
                    line.setAttribute('stroke', '#1c5da1');
                    line.setAttribute('stroke-width', '2');
                    line.setAttribute('stroke-linecap', 'round');
                    line.setAttribute('marker-end', 'url(#family-arrow)');
                    svg.appendChild(line);

                    const badge = document.createElement('span');
                    badge.className = 'family-relation-badge';
                    badge.setAttribute('data-family-relation-badge', '');
                    badge.style.left = mx + 'px';
                    badge.style.top = my + 'px';
                    badge.title = [
                        relation.getAttribute('data-from-label') || '',
                        relation.getAttribute('data-label') || '',
                        relation.getAttribute('data-to-label') || ''
                    ].filter(Boolean).join(' ');

                    const label = document.createElement('span');
                    label.textContent = relation.getAttribute('data-label') || '';
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

                    map.appendChild(badge);
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
