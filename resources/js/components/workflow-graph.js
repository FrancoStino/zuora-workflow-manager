// Professional JointJS Pure JavaScript Workflow Graph
// Based on JointJS template with Zuora workflow integration

import {dia, highlighters, linkTools, shapes} from '@joint/core';
import {DirectedGraph} from '@joint/layout-directed-graph';

// Styles and constants from template
const unit = 4;
const bevel = 2 * unit;
const spacing = 2 * unit;
const flowSpacing = unit / 2;

const fontAttributes = {
    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
    fontStyle: "normal",
    fontSize: 14,
    lineHeight: 18
};

// Global variables for graph and paper
let currentGraph = null;
let currentPaper = null;


// Flowchart element creation functions (from template)
function createStart(x, y, text) {
    console.log('Creating start at:', x, y);
    const element = new shapes.standard.Rectangle({
        position: {x: x + 10, y: y + 5},
        size: {width: 80, height: 50},
        z: 1,
        attrs: {
            body: {
                class: "jj-start-body",
                rx: 25,
                ry: 25
            },
            label: {
                class: "jj-start-text",
                ...fontAttributes,
                fontSize: fontAttributes.fontSize * 1.2,
                fontWeight: "bold",
                text
            }
        }
    });
    console.log('Start element created:', element);
    return element;
}

function createEnd(x, y, text) {
    return new shapes.standard.Rectangle({
        position: {x: x + 10, y: y + 5},
        size: {width: 80, height: 50},
        z: 1,
        attrs: {
            body: {
                class: "jj-end-body",
                rx: 25,
                ry: 25
            },
            label: {
                class: "jj-end-text",
                ...fontAttributes,
                fontSize: fontAttributes.fontSize * 1.2,
                fontWeight: "bold",
                text
            }
        }
    });
}

function createStep(x, y, text) {
    return new shapes.standard.Rectangle({
        position: {x, y},
        size: {width: 120, height: 60},
        z: 1,
        attrs: {
            body: {
                class: "jj-step-body",
                rx: 8,
                ry: 8
            },
            label: {
                class: "jj-step-text",
                ...fontAttributes,
                text,
                textWrap: {
                    width: -spacing,
                    height: -spacing
                }
            }
        }
    });
}

function createDecision(x, y, text) {
    return new shapes.standard.Path({
        position: {x: x - 30, y: y - 10},
        size: {width: 160, height: 80},
        z: 1,
        attrs: {
            body: {
                class: "jj-decision-body",
                d: "M 0 calc(0.5 * h) L calc(0.5 * w) 0 L calc(w) calc(0.5 * h) L calc(0.5 * w) calc(h) Z"
            },
            label: {
                ...fontAttributes,
                class: "jj-decision-text",
                text
            }
        }
    });
}

function createFlow(source, target, sourceAnchor = "right", targetAnchor = "left", label = "") {
    const link = new shapes.standard.Link({
        source: {id: source.id, anchor: {name: sourceAnchor}},
        target: {id: target.id, anchor: {name: targetAnchor}},
        z: 2,
        attrs: {
            line: {
                class: "jj-flow-line",
                targetMarker: {
                    class: "jj-flow-arrowhead",
                    d: 'M 0 0 L ' + (2 * unit) + ' ' + unit + ' L ' + (2 * unit) + ' -' + unit + ' Z'
                }
            },
            outline: {
                class: "jj-flow-outline",
                connection: true
            }
        },
        markup: [
            {
                tagName: "path",
                selector: "wrapper",
                attributes: {
                    fill: "none",
                    cursor: "pointer",
                    stroke: "transparent",
                    "stroke-linecap": "round"
                }
            },
            {
                tagName: "path",
                selector: "outline",
                attributes: {
                    fill: "none",
                    "pointer-events": "none"
                }
            },
            {
                tagName: "path",
                selector: "line",
                attributes: {
                    fill: "none",
                    "pointer-events": "none"
                }
            }
        ]
    });

    if (label) {
        link.labels([{
            position: {
                distance: 0.5,  // Position at 50% of the link (center)
                offset: 0       // No offset from the link line
            },
            attrs: {
                labelBody: {
                    class: "jj-flow-label-body",
                    ref: "labelText",
                    refX: -8,
                    refY: -8,
                    refWidth: '100%',
                    refHeight: '100%',
                    refWidth2: 16,
                    refHeight2: 16,
                    rx: 4,
                    ry: 4
                },
                labelText: {
                    class: "jj-flow-label-text",
                    text: label,
                    textAnchor: "middle",
                    textVerticalAnchor: "middle",
                    ...fontAttributes
                }
            },
            markup: [
                {
                    tagName: "rect",
                    selector: "labelBody"
                },
                {
                    tagName: "text",
                    selector: "labelText"
                }
            ]
        }]);
    }

    return link;
}

// Parse Zuora workflow data into JointJS elements (without links)
function parseZuoraWorkflow(workflowData) {
    const elements = [];

    if (!workflowData) {
        return {elements};
    }

    try {
        const workflow = typeof workflowData === 'string' ? JSON.parse(workflowData) : workflowData;

        if (!workflow || typeof workflow !== 'object') {
            throw new Error('Invalid workflow data format');
        }

        // Create Start node (position will be set by DirectedGraph layout)
        const startX = 0;
        const startY = 0;

        const startNode = createStart(startX, startY, "Start");
        startNode.taskId = 'start'; // Store identifier for linking
        elements.push(startNode);

        // Process workflow tasks
        if (workflow.tasks && Array.isArray(workflow.tasks)) {
            if (workflow.tasks.length === 0) {
                throw new Error('Workflow contains no tasks');
            }

            // Sort tasks by ID to ensure consistent ordering
            const sortedTasks = [...workflow.tasks].sort((a, b) => a.id - b.id);

            sortedTasks.forEach((task, index) => {
                if (!task || !task.id) {
                    console.warn('Skipping invalid task at index ' + index + ':', task);
                    return;
                }

                const actionType = task.action_type || task.type || 'task';

                // Initial position (will be recalculated by DirectedGraph layout)
                const x = 0;
                const y = 0;

                console.log('Creating task', task.id, '(position will be set by layout)');

                // Use Decision shape for approval/wait tasks, Step for others
                let node;
                if (actionType === 'Iterate') {
                    // Special shape for iterate tasks
                    node = createDecision(x, y, task.name || 'Task ' + task.id);
                } else {
                    node = createStep(x, y, task.name || 'Task ' + task.id);
                }

                // Store original task ID and full task data for linking and modal display
                node.taskId = task.id.toString();
                node.taskData = task; // Store complete task data
                elements.push(node);
            });
        } else {
            throw new Error('Workflow data must contain a tasks array');
        }

        // Create End node (position will be set by DirectedGraph layout)
        const taskCount = workflow.tasks?.length || 0;
        if (taskCount === 0) {
            throw new Error('No valid tasks found in workflow');
        }

        const endX = 0;
        const endY = 0;

        const endNode = createEnd(endX, endY, "End");
        endNode.taskId = 'end'; // Store identifier for linking
        elements.push(endNode);

    } catch (error) {
        console.error('Error parsing workflow data:', error);
        return {elements: []};
    }

    return {elements};
}

// Create workflow links using positioned elements
function createWorkflowLinks(workflowData, elements) {
    const links = [];

    if (!workflowData || !elements || elements.length === 0) {
        return links;
    }

    try {
        const workflow = typeof workflowData === 'string' ? JSON.parse(workflowData) : workflowData;

        // Helper function to find element by taskId
        const findElementByTaskId = (taskId) => {
            return elements.find(el => el.taskId === taskId);
        };

        // Create links based on linkages (Zuora format)
        if (workflow.linkages && Array.isArray(workflow.linkages)) {
            workflow.linkages.forEach((linkage, index) => {
                try {
                    let fromElement, toElement;

                    // Handle Zuora linkage format
                    if (linkage.source_workflow_id !== null && linkage.source_task_id === null) {
                        // This is a start linkage (source_workflow_id present, source_task_id null)
                        fromElement = findElementByTaskId('start');
                    } else if (linkage.source_task_id !== null) {
                        // Normal task-to-task linkage
                        fromElement = findElementByTaskId(linkage.source_task_id.toString());
                    }

                    if (linkage.target_task_id !== null) {
                        // Link to a task
                        toElement = findElementByTaskId(linkage.target_task_id.toString());
                    } else if (linkage.target_workflow_id !== null) {
                        // Link to end (target_workflow_id present)
                        toElement = findElementByTaskId('end');
                    }

                    if (fromElement && toElement) {
                        console.log('Creating link from', fromElement.taskId, 'to', toElement.taskId, 'with label:', linkage.linkage_type);
                        // Always show linkage_type label, default to empty string if not present
                        const labelText = linkage.linkage_type || "";
                        const link = createFlow(fromElement, toElement, "right", "left", labelText);
                        links.push(link);
                    } else {
                        console.warn('Could not find elements for linkage:', linkage);
                    }
                } catch (linkError) {
                    console.warn('Skipping invalid linkage at index ' + index + ':', linkage, linkError);
                }
            });

            // Find terminal tasks (tasks with no outgoing links) and connect to end
            const tasksWithOutgoing = new Set();
            workflow.linkages.forEach(linkage => {
                if (linkage.source_task_id !== null) {
                    tasksWithOutgoing.add(linkage.source_task_id);
                }
            });

            workflow.tasks?.forEach(task => {
                try {
                    const taskId = task.id;
                    if (!tasksWithOutgoing.has(taskId)) {
                        const fromElement = findElementByTaskId(taskId.toString());
                        const toElement = findElementByTaskId('end');

                        if (fromElement && toElement) {
                            const link = createFlow(fromElement, toElement, "right", "left", "Complete");
                            links.push(link);
                        }
                    }
                } catch (taskLinkError) {
                    console.warn('Error creating terminal link for task ' + task.id + ':', taskLinkError);
                }
            });
        } else {
            // Fallback: create sequential connections
            const taskIds = workflow.tasks?.map(t => t.id.toString()) || [];
            if (taskIds.length > 0) {
                try {
                    // Start to first task
                    const startEl = findElementByTaskId('start');
                    const firstTaskEl = findElementByTaskId(taskIds[0]);
                    if (startEl && firstTaskEl) {
                        links.push(createFlow(startEl, firstTaskEl));
                    }

                    // Task to task
                    for (let i = 0; i < taskIds.length - 1; i++) {
                        const currentTaskEl = findElementByTaskId(taskIds[i]);
                        const nextTaskEl = findElementByTaskId(taskIds[i + 1]);
                        if (currentTaskEl && nextTaskEl) {
                            links.push(createFlow(currentTaskEl, nextTaskEl));
                        }
                    }

                    // Last task to end
                    const lastTaskEl = findElementByTaskId(taskIds[taskIds.length - 1]);
                    const endEl = findElementByTaskId('end');
                    if (lastTaskEl && endEl) {
                        links.push(createFlow(lastTaskEl, endEl));
                    }
                } catch (fallbackError) {
                    console.error('Error creating fallback links:', fallbackError);
                }
            }
        }

    } catch (error) {
        console.error('Error creating workflow links:', error);
    }

    return links;
}

// Initialize workflow graph
function initWorkflowGraph(containerId, workflowData) {
    console.log('=== JOINTJS WORKFLOW GRAPH INIT START ===');
    console.log('Container ID:', containerId);
    console.log('Workflow data type:', typeof workflowData);

    const container = document.getElementById(containerId);
    if (!container) {
        console.error('Container not found:', containerId);
        return {success: false, error: 'Container not found'};
    }

    // Clear container
    container.innerHTML = '';

    try {
        if (!workflowData) {
            console.error('No workflow data provided');
            container.innerHTML = '<div style="padding: 20px; color: red; text-align: center;">No workflow data provided</div>';
            return {success: false, error: 'No workflow data provided'};
        }

        // Note: Container styling is primarily handled by Tailwind classes in Blade template
        // Only set styles that are absolutely required for JointJS functionality
        container.style.overflow = 'hidden'; // Required for proper JointJS rendering

        // IMPORTANT: The border class in the Blade template (border-white/0) is required
        // for JointJS to properly calculate container bounds. Even a transparent border
        // ensures the container has proper dimensions for rendering.

        console.log('Container dimensions:', container.offsetWidth, container.offsetHeight);
        console.log('Container styles:', container.style.cssText);

        // Create graph
        currentGraph = new dia.Graph({}, {cellNamespace: shapes});

        // Create paper with settings similar to the original template
        currentPaper = new dia.Paper({
            el: container,
            model: currentGraph,
            cellViewNamespace: shapes,
            width: "100%",
            height: "100%",
            async: true,
            sorting: dia.Paper.sorting.APPROX,
            snapLabels: true,
            clickThreshold: 10,
            interactive: {
                linkMove: false
            },
            gridSize: 5,
            defaultConnectionPoint: {
                name: "boundary",
                args: {
                    offset: spacing,
                    extrapolate: true
                }
            },
            defaultRouter: {name: "rightAngle", args: {margin: unit * 7}},
            defaultConnector: {
                name: "rounded",
                args: {radius: 8}
            },
            // Add the default link settings
            defaultLink: new shapes.standard.Link({
                z: 2,
                attrs: {
                    line: {
                        class: "jj-flow-line",
                        targetMarker: {
                            class: "jj-flow-arrowhead",
                            d: 'M 0 0 L ' + (2 * unit) + ' ' + unit + ' L ' + (2 * unit) + ' -' + unit + ' Z'
                        }
                    },
                    outline: {
                        class: "jj-flow-outline",
                        connection: true
                    }
                },
                markup: [
                    {
                        tagName: "path",
                        selector: "wrapper",
                        attributes: {
                            fill: "none",
                            cursor: "pointer",
                            stroke: "transparent",
                            "stroke-linecap": "round"
                        }
                    },
                    {
                        tagName: "path",
                        selector: "outline",
                        attributes: {
                            fill: "none",
                            "pointer-events": "none"
                        }
                    },
                    {
                        tagName: "path",
                        selector: "line",
                        attributes: {
                            fill: "none",
                            "pointer-events": "none"
                        }
                    }
                ],
                defaultLabel: {
                    attrs: {
                        labelBody: {
                            class: "jj-flow-label-body",
                            ref: "labelText",
                            d: 'M calc(x-' + spacing + ') calc(y-' + spacing + ') m 0 ' + bevel + ' l ' + bevel + ' -' + bevel + ' h calc(w+' + (2 * (spacing - bevel)) + ') l ' + bevel + ' ' + bevel + ' v calc(h+' + (2 * (spacing - bevel)) + ') l -' + bevel + ' ' + bevel + ' H calc(x-' + spacing + '-' + bevel + ') l -' + bevel + ' -' + bevel + ' Z'
                        },
                        labelText: {
                            ...fontAttributes,
                            class: "jj-flow-label-text",
                            textAnchor: "middle",
                            textVerticalAnchor: "middle",
                            fontStyle: "italic"
                        }
                    },
                    markup: [
                        {
                            tagName: "path",
                            selector: "labelBody"
                        },
                        {
                            tagName: "text",
                            selector: "labelText"
                        }
                    ]
                }
            })
        });

        console.log('Paper created:', currentPaper);

        // Parse workflow to get elements
        const {elements} = parseZuoraWorkflow(workflowData);

        if (!elements || elements.length === 0) {
            throw new Error('No valid elements found in workflow data');
        }

        console.log('Created elements:', elements.length);
        console.log('Elements:', elements);

        // Add elements to graph first
        currentGraph.addCells(elements);
        console.log('Elements added to graph');

        // Now create links using the positioned elements
        const links = createWorkflowLinks(workflowData, elements);
        console.log('Created links:', links.length);

        // Add links to graph
        if (links.length > 0) {
            currentGraph.addCells(links);
            console.log('Links added to graph');
        }

        // Apply DirectedGraph layout algorithm for proper spacing
        console.log('Applying DirectedGraph layout...');
        DirectedGraph.layout(currentGraph, {
            nodeSep: 80,        // Horizontal spacing between nodes
            edgeSep: 40,        // Spacing between edges
            rankSep: 150,       // Vertical spacing between ranks
            rankDir: 'LR',      // Left to Right direction
            marginX: 50,        // Margin on X axis
            marginY: 50,        // Margin on Y axis
            resizeCluster: true,
            clusterPadding: {top: 10, left: 10, right: 10, bottom: 10}
        });
        console.log('Layout applied');

        console.log('Graph cells:', currentGraph.getCells().length);

        // Function to fit content to viewport
        const fitToViewport = () => {
            const graphBBox = currentGraph.getBBox();
            console.log('Fitting to viewport, bbox:', graphBBox);

            if (graphBBox.width > 0 && graphBBox.height > 0) {
                currentPaper.transformToFitContent({
                    padding: 60,
                    minScale: 0.4,
                    maxScale: 1.5,
                    verticalAlign: 'middle',
                    horizontalAlign: 'middle',
                    useModelGeometry: true
                });
                console.log('Content fitted to viewport');
            }
        };

        // Wait for paper to be fully rendered using requestAnimationFrame
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                fitToViewport();
            });
        });

        // Handle window resize with requestAnimationFrame debounce
        let resizeRafId;
        let resizeTimeout;
        const handleResize = () => {
            // Cancel any pending animation frame
            if (resizeRafId) {
                cancelAnimationFrame(resizeRafId);
            }

            // Cancel previous timeout
            clearTimeout(resizeTimeout);

            // Wait 150ms of "silence", then use rAF for smooth animation
            resizeTimeout = setTimeout(() => {
                resizeRafId = requestAnimationFrame(() => {
                    fitToViewport();
                });
            }, 150);
        };

        window.addEventListener('resize', handleResize);

        // Observe container visibility changes (for tab switching)
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.intersectionRatio > 0) {
                    console.log('Container became visible, refitting...');
                    // Use double rAF to ensure container is fully rendered
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            fitToViewport();
                        });
                    });
                }
            });
        }, {threshold: 0.1});

        observer.observe(container);

        // Cleanup on destroy
        const cleanup = () => {
            window.removeEventListener('resize', handleResize);
            observer.disconnect();
            clearTimeout(resizeTimeout);
            if (resizeRafId) {
                cancelAnimationFrame(resizeRafId);
            }
        };

        // Store cleanup function for potential future use
        container._workflowCleanup = cleanup;

        // Add theme switcher
        const themeSwitch = document.createElement('div');
        themeSwitch.className = 'theme-switch';
        themeSwitch.title = 'Switch between light and dark mode';
        themeSwitch.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 24 24" fill="none" stroke="#dde6ed" stroke-linecap="round" stroke-linejoin="round" class="light-icon"><path d="M12 18.5C15.5899 18.5 18.5 15.5899 18.5 12C18.5 8.41015 15.5899 5.5 12 5.5C8.41015 5.5 5.5 8.41015 5.5 12C5.5 15.5899 8.41015 18.5 12 18.5Z" stroke-width="1.5" /><path d="M19.14 19.14L19.01 19.01M19.01 4.99L19.14 4.86L19.01 4.99ZM4.86 19.14L4.99 19.01L4.86 19.14ZM12 2.08V2V2.08ZM12 22V21.92V22ZM2.08 12H2H2.08ZM22 12H21.92H22ZM4.99 4.99L4.86 4.86L4.99 4.99Z" stroke-width="2" /></svg><svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 24 24" fill="#131e29" class="dark-icon"><path d="M12.0557 3.59974C12.2752 3.2813 12.2913 2.86484 12.0972 2.53033C11.9031 2.19582 11.5335 2.00324 11.1481 2.03579C6.02351 2.46868 2 6.76392 2 12C2 17.5228 6.5228 22 12 22C17.236 22 21.5313 17.9764 21.9642 12.8518C21.9967 12.4664 21.8041 12.0968 21.4696 11.9027C21.1351 11.7086 20.7187 11.7248 20.4002 11.9443C19.4341 12.6102 18.2641 13 17 13C13.6863 13 11 10.3137 11 6.99996C11 5.73589 11.3898 4.56587 12.0557 3.59974Z" /></svg><div class="switch"></div>';
        container.appendChild(themeSwitch);

        themeSwitch.addEventListener('click', () => {
            console.log('Theme switch clicked');
            document.body.classList.toggle('light-theme');
            console.log('Body classes:', document.body.className);
        });

        // Add interaction event listeners
        const {mask: MaskHighlighter, stroke: StrokeHighlighter} = highlighters;

        currentPaper.on('cell:mouseenter', (cellView, evt) => {
            let selector, padding;
            if (cellView.model.isLink()) {
                if (StrokeHighlighter.get(cellView, 'selection')) return;
                selector = {label: 0, selector: 'labelBody'};
                padding = unit / 2;
            } else {
                selector = 'body';
                padding = unit;
            }
            const frame = MaskHighlighter.add(cellView, selector, 'frame', {
                padding,
                layer: dia.Paper.Layers.FRONT,
                attrs: {
                    'stroke-width': 1.5,
                    'stroke-linejoin': 'round'
                }
            });
            frame.el.classList.add('jj-frame');
        });

        currentPaper.on('cell:mouseleave', (cellView) => {
            MaskHighlighter.removeAll(currentPaper, 'frame');
        });

        // Add click event for nodes to show task details slide-over
        currentPaper.on('element:pointerclick', (cellView) => {
            const element = cellView.model;

            // Check if this is a task node (not Start or End)
            if (element.taskData) {
                console.log('Task clicked:', element.taskData);

                // Try to find the corresponding table row and trigger the slide-over action
                const taskId = element.taskData.id;
                console.log('Looking for table row with task ID:', taskId);

                // Try different selectors to find the table row
                let tableRow = document.querySelector(`tr[data-id="${taskId}"]`);

                if (!tableRow) {
                    // Try to find by task_id column content
                    const rows = document.querySelectorAll('table tbody tr');
                    for (const row of rows) {
                        const firstCell = row.querySelector('td:first-child');
                        if (firstCell && firstCell.textContent.trim() === taskId.toString()) {
                            tableRow = row;
                            break;
                        }
                    }
                }

                console.log('Found table row:', tableRow);

                if (tableRow) {
                    // Find the button and click it
                    const buttons = tableRow.querySelectorAll('button');
                    console.log('Found buttons in row:', buttons);

                    let actionButton = null;
                    for (const button of buttons) {
                        const wireClick = button.getAttribute('wire:click');
                        console.log('Button wire:click:', wireClick);
                        if (wireClick && wireClick.includes('viewDetails')) {
                            actionButton = button;
                            break;
                        }
                    }

                    if (actionButton) {
                        console.log('Found viewDetails button, dispatching click event');
                        // Try multiple ways to trigger the click
                        try {
                            actionButton.click();
                        } catch (e) {
                            actionButton.dispatchEvent(new Event('click', {bubbles: true, cancelable: true}));
                        }
                    } else {
                        console.log('viewDetails button not found, triggering click on table row');
                        tableRow.click();
                    }
                } else {
                    // Table row not found - could show a message or do nothing
                    console.log('Table row not found, cannot open slide-over');
                }
            } else if (element.taskId === 'start') {
                console.log('Start node clicked');
            } else if (element.taskId === 'end') {
                console.log('End node clicked');
            }
        });

        currentPaper.on('link:pointerclick', (cellView) => {
            currentPaper.removeTools();
            dia.HighlighterView.removeAll(currentPaper);
            const snapAnchor = function (coords, endView) {
                const bbox = endView.model.getBBox();
                const point = bbox.pointNearestToPoint(coords);
                const center = bbox.center();
                const snapRadius = 10;
                if (Math.abs(point.x - center.x) < snapRadius) {
                    point.x = center.x;
                }
                if (Math.abs(point.y - center.y) < snapRadius) {
                    point.y = center.y;
                }
                return point;
            };
            const toolsView = new dia.ToolsView({
                tools: [
                    new linkTools.TargetAnchor({
                        snap: snapAnchor,
                        resetAnchor: cellView.model.prop(['target', 'anchor'])
                    }),
                    new linkTools.SourceAnchor({
                        snap: snapAnchor,
                        resetAnchor: cellView.model.prop(['source', 'anchor'])
                    })
                ]
            });
            toolsView.el.classList.add('jj-flow-tools');
            cellView.addTools(toolsView);

            const strokeHighlighter = StrokeHighlighter.add(
                cellView,
                'root',
                'selection',
                {
                    layer: dia.Paper.Layers.BACK
                }
            );
            strokeHighlighter.el.classList.add('jj-flow-selection');
        });

        // Pan/Drag functionality
        let isPanning = false;
        let startPanX = 0;
        let startPanY = 0;
        let lastPanX = 0;
        let lastPanY = 0;

        currentPaper.on('blank:pointerdown', (evt, x, y) => {
            currentPaper.removeTools();
            dia.HighlighterView.removeAll(currentPaper);

            // Start panning
            isPanning = true;
            startPanX = evt.clientX;
            startPanY = evt.clientY;
            lastPanX = evt.clientX;
            lastPanY = evt.clientY;

            // Change cursor to grabbing
            container.style.cursor = 'grabbing';
        });

        currentPaper.on('blank:pointermove', (evt, x, y) => {
            if (!isPanning) return;

            // Calculate the delta movement from last position
            const deltaX = evt.clientX - lastPanX;
            const deltaY = evt.clientY - lastPanY;

            // Update last position
            lastPanX = evt.clientX;
            lastPanY = evt.clientY;

            // Get current translation
            const currentTranslate = currentPaper.translate();

            // Apply incremental translation
            currentPaper.translate(
                currentTranslate.tx + deltaX,
                currentTranslate.ty + deltaY
            );
        });

        currentPaper.on('blank:pointerup', (evt) => {
            if (!isPanning) return;

            // Stop panning
            isPanning = false;

            // Reset cursor
            container.style.cursor = 'grab';
        });

        // Set initial cursor style for blank area
        container.style.cursor = 'grab';

        // Mouse wheel zoom functionality
        const MIN_SCALE = 0.2;
        const MAX_SCALE = 3.0;
        const ZOOM_STEP = 0.1;

        container.addEventListener('wheel', (evt) => {
            evt.preventDefault();

            // Get current scale
            const currentScale = currentPaper.scale();
            const sx = currentScale.sx;

            // Calculate new scale based on wheel direction
            const delta = evt.deltaY > 0 ? -ZOOM_STEP : ZOOM_STEP;
            let newScale = sx + delta;

            // Clamp scale to min/max values
            newScale = Math.max(MIN_SCALE, Math.min(MAX_SCALE, newScale));

            // Get mouse position relative to the paper
            const paperRect = container.getBoundingClientRect();
            const mouseX = evt.clientX - paperRect.left;
            const mouseY = evt.clientY - paperRect.top;

            // Get current translation
            const currentTranslate = currentPaper.translate();

            // Calculate the point in the graph that's under the mouse
            const pointBeforeZoom = {
                x: (mouseX - currentTranslate.tx) / sx,
                y: (mouseY - currentTranslate.ty) / sx
            };

            // Apply new scale
            currentPaper.scale(newScale, newScale);

            // Calculate new translation to keep the point under the mouse
            const newTranslate = {
                tx: mouseX - pointBeforeZoom.x * newScale,
                ty: mouseY - pointBeforeZoom.y * newScale
            };

            // Apply new translation
            currentPaper.translate(newTranslate.tx, newTranslate.ty);
        }, {passive: false});

        console.log('JointJS workflow graph rendered successfully');
        console.log('=== JOINTJS WORKFLOW GRAPH INIT END ===');
        return {success: true};
    } catch (error) {
        console.error('Failed to render JointJS workflow graph:', error);
        container.innerHTML = '<div style="padding: 20px; color: red; text-align: center;">Error rendering graph: ' + error.message + '</div>';
        return {success: false, error: error.message};
    }
}

// Export for ES6 modules
export {initWorkflowGraph};

// Also make available globally for backward compatibility
window.initWorkflowGraph = initWorkflowGraph;
