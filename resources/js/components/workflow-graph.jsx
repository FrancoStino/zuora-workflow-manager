import React, { useCallback, useMemo } from 'react';
import { createRoot } from 'react-dom/client';
import {
  ReactFlow,
  Background,
  Controls,
  MiniMap,
  useNodesState,
  useEdgesState,
  addEdge,
  Panel,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';

// Import icons from react-icons
import { FaPlay, FaStop, FaCog, FaCheckCircle, FaTimesCircle } from 'react-icons/fa';

// Custom node components
const StartNode = ({ data }) => (
  <div className="px-4 py-2 shadow-md rounded-full bg-green-500 border-2 border-green-700">
    <div className="flex items-center">
      <FaPlay className="text-white mr-2" />
      <div className="text-white font-bold">{data.label}</div>
    </div>
  </div>
);

const EndNode = ({ data }) => (
  <div className="px-4 py-2 shadow-md rounded-full bg-red-500 border-2 border-red-700">
    <div className="flex items-center">
      <FaStop className="text-white mr-2" />
      <div className="text-white font-bold">{data.label}</div>
    </div>
  </div>
);

const TaskNode = ({ data }) => {
  const getActionIcon = (actionType) => {
    const icons = {
      create: <FaCheckCircle className="text-white mr-2" />,
      update: <FaCog className="text-white mr-2" />,
      delete: <FaTimesCircle className="text-white mr-2" />,
      query: <FaCog className="text-white mr-2" />,
      default: <FaCog className="text-white mr-2" />
    };
    return icons[actionType] || icons.default;
  };

  const getActionColor = (actionType) => {
    const colors = {
      create: 'bg-green-500 border-green-700',
      update: 'bg-purple-500 border-purple-700',
      delete: 'bg-red-500 border-red-700',
      query: 'bg-blue-500 border-blue-700',
      callout: 'bg-pink-500 border-pink-700',
      email: 'bg-yellow-500 border-yellow-700',
      wait: 'bg-gray-500 border-gray-700',
      approval: 'bg-orange-500 border-orange-700',
      export: 'bg-indigo-500 border-indigo-700',
      iterate: 'bg-yellow-500 border-yellow-700',
      cancel: 'bg-red-500 border-red-700',
      default: 'bg-blue-500 border-blue-700'
    };
    return colors[actionType] || colors.default;
  };

  return (
    <div className={`px-4 py-2 shadow-md rounded-lg border-2 ${getActionColor(data.actionType)} max-w-xs`}>
      <div className="flex items-center">
        {getActionIcon(data.actionType)}
        <div className="text-white font-medium text-sm break-words">{data.label}</div>
      </div>
    </div>
  );
};

// Node types mapping
const nodeTypes = {
  start: StartNode,
  end: EndNode,
  task: TaskNode,
};

const WorkflowGraph = ({ workflowData, options = {} }) => {
  const defaultOptions = {
    layout: 'layered',
    interactive: true,
    theme: 'light',
    showControls: true,
    showMiniMap: true,
    ...options
  };

  // Parse Zuora workflow data
  const { nodes: initialNodes, edges: initialEdges } = useMemo(() => {
    console.log('Parsing workflow data:', workflowData);
    const result = parseZuoraWorkflow(workflowData);
    console.log('Parsed nodes:', result.nodes.length, 'edges:', result.edges.length);
    return result;
  }, [workflowData]);

  const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes);
  const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges);

  const onConnect = useCallback(
    (params) => setEdges((eds) => addEdge(params, eds)),
    [setEdges]
  );

  // Check if we have valid data
  if (!nodes || nodes.length === 0) {
    return (
      <div className="w-full h-full flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="text-gray-400 text-lg mb-2">📊</div>
          <div className="text-gray-600 font-medium">No workflow data available</div>
          <div className="text-gray-400 text-sm">Please provide valid workflow data to display the graph</div>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full h-full">
      <ReactFlow
        nodes={nodes}
        edges={edges}
        onNodesChange={onNodesChange}
        onEdgesChange={onEdgesChange}
        onConnect={onConnect}
        nodeTypes={nodeTypes}
        fitView
        className="bg-gray-50"
        minZoom={0.1}
        maxZoom={2}
      >
        <Background color="#aaa" gap={16} />
        {defaultOptions.showControls && <Controls />}
        {defaultOptions.showMiniMap && <MiniMap />}

        <Panel position="top-right">
          <div className="bg-white p-2 rounded shadow-md">
            <div className="text-sm font-medium mb-2">Workflow Controls</div>
            <div className="flex gap-2">
              <button
                className="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600"
                onClick={() => {
                  // Fit to view - this needs to be handled differently in ReactFlow
                  console.log('Fit view clicked');
                }}
              >
                Fit View
              </button>
              <button
                className="px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600"
                onClick={() => {
                  // Reset zoom - this needs to be handled differently in ReactFlow
                  console.log('Reset zoom clicked');
                }}
              >
                Reset Zoom
              </button>
            </div>
          </div>
        </Panel>
      </ReactFlow>
    </div>
  );
};

// Parse Zuora workflow data into ReactFlow format
function parseZuoraWorkflow(workflowData) {
  const nodes = [];
  const edges = [];

  if (!workflowData) {
    return { nodes, edges };
  }

  const workflow = typeof workflowData === 'string' ? JSON.parse(workflowData) : workflowData;

  // Start node
  nodes.push({
    id: 'start',
    type: 'start',
    position: { x: 0, y: 0 },
    data: { label: 'Start' },
  });

  // Process workflow tasks
  if (workflow.tasks && Array.isArray(workflow.tasks)) {
    workflow.tasks.forEach((task, index) => {
      const actionType = task.action_type || task.type || 'task';
      nodes.push({
        id: task.id.toString(),
        type: 'task',
        position: { x: (index + 1) * 200, y: 0 },
        data: {
          label: task.name || `Task ${task.id}`,
          actionType: actionType,
          originalData: task
        },
      });
    });
  }

  // End node
  const endNodeX = (workflow.tasks?.length || 0) * 200 + 200;
  nodes.push({
    id: 'end',
    type: 'end',
    position: { x: endNodeX, y: 0 },
    data: { label: 'End' },
  });

  // Create edges based on linkages (Zuora format)
  if (workflow.linkages && Array.isArray(workflow.linkages)) {
    workflow.linkages.forEach(linkage => {
      let fromId, toId;

      // Handle Zuora linkage format
      if (linkage.source_workflow_id !== null && linkage.source_task_id === null) {
        // Start linkage
        fromId = 'start';
      } else if (linkage.source_task_id !== null) {
        fromId = linkage.source_task_id.toString();
      }

      if (linkage.target_task_id !== null) {
        toId = linkage.target_task_id.toString();
      } else if (linkage.target_workflow_id !== null) {
        // End linkage
        toId = 'end';
      }

      if (fromId && toId && fromId !== toId) {
        edges.push({
          id: `${fromId}-${toId}`,
          source: fromId,
          target: toId,
          label: linkage.linkage_type || '',
          type: 'smoothstep',
        });
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
      const taskId = task.id;
      if (!tasksWithOutgoing.has(taskId)) {
        edges.push({
          id: `${taskId}-end`,
          source: taskId.toString(),
          target: 'end',
          label: 'Complete',
          type: 'smoothstep',
        });
      }
    });
  } else {
    // Fallback: create sequential connections
    const taskIds = workflow.tasks?.map(t => t.id.toString()) || [];
    if (taskIds.length > 0) {
      edges.push({
        id: 'start-' + taskIds[0],
        source: 'start',
        target: taskIds[0],
        type: 'smoothstep'
      });

      for (let i = 0; i < taskIds.length - 1; i++) {
        edges.push({
          id: taskIds[i] + '-' + taskIds[i + 1],
          source: taskIds[i],
          target: taskIds[i + 1],
          type: 'smoothstep'
        });
      }

      edges.push({
        id: taskIds[taskIds.length - 1] + '-end',
        source: taskIds[taskIds.length - 1],
        target: 'end',
        type: 'smoothstep'
      });
    }
  }

  return { nodes, edges };
}

// Legacy function for backward compatibility
export function initWorkflowGraph(containerId, workflowData) {
  console.log('=== WORKFLOW GRAPH INIT START ===');
  console.log('Container ID:', containerId);
  console.log('Workflow data type:', typeof workflowData);
  console.log('Workflow data keys:', workflowData ? Object.keys(workflowData) : 'null');
  console.log('React available:', typeof React !== 'undefined');
  console.log('createRoot available:', typeof createRoot !== 'undefined');

  const container = document.getElementById(containerId);
  if (!container) {
    console.error('Container not found:', containerId);
    return { success: false, error: 'Container not found' };
  }

  // Simple test render first
  try {
    console.log('Testing simple React render...');
    container.innerHTML = '<div style="padding: 20px; color: green;">React is working! Loading workflow...</div>';

    // Small delay to show the test message
    setTimeout(() => {
      try {
        if (!workflowData) {
          console.error('No workflow data provided');
          container.innerHTML = `
            <div class="p-8 text-center">
              <div class="text-gray-600 font-semibold mb-2">No Data</div>
              <div class="text-sm text-gray-600">No workflow data was provided.</div>
            </div>
          `;
          return { success: false, error: 'No workflow data provided' };
        }

        // Ensure container has proper styling for ReactFlow
        container.style.width = '100%';
        container.style.height = '100%';
        container.style.position = 'relative';

        console.log('Creating React root...');
        // Create React root and render component
        const root = createRoot(container);
        console.log('Rendering WorkflowGraph component...');
        root.render(
          React.createElement(WorkflowGraph, {
            key: `workflow-${containerId}-${Date.now()}`, // Unique key to prevent caching issues
            workflowData: workflowData,
            options: {
              showControls: true,
              showMiniMap: true,
              interactive: true
            }
          })
        );

        console.log('React workflow graph rendered successfully');
        console.log('=== WORKFLOW GRAPH INIT END ===');
        return { success: true };
      } catch (error) {
        console.error('Failed to render React workflow graph:', error);
        container.innerHTML = `
          <div class="p-8 text-center">
            <div class="text-red-600 font-semibold mb-2">Error rendering graph</div>
            <div class="text-sm text-gray-600">${error.message}</div>
          </div>
        `;
        return { success: false, error: error.message };
      }
    }, 500);

  } catch (error) {
    console.error('Failed to do simple test render:', error);
    container.innerHTML = `
      <div class="p-8 text-center">
        <div class="text-red-600 font-semibold mb-2">Error: Basic render failed</div>
        <div class="text-sm text-gray-600">${error.message}</div>
      </div>
    `;
    return { success: false, error: error.message };
  }
}

export default WorkflowGraph;