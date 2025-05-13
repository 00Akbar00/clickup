export const workspaceData = {
  teamspaces: [
    {
      id: 1,
      name: 'Teamspace 1',
      projects: [
        {
          id: 1,
          name: 'Project 1',
          lists: [
            {
              id: 1,
              name: 'List 1',
              tasks: [
                { id: 1, name: 'Task 1' },
                { id: 2, name: 'Task 2' },
                { id: 3, name: 'Task 3' }
              ]
            },
            {
              id: 2,
              name: 'List 2',
              tasks: [
                { id: 4, name: 'Task 4' },
                { id: 5, name: 'Task 5' }
              ]
            }
          ]
        },
        {
          id: 2,
          name: 'Project 2',
          lists: [
            {
              id: 3,
              name: 'List 1',
              tasks: [
                { id: 6, name: 'Task 6' },
                { id: 7, name: 'Task 7' }
              ]
            }
          ]
        }
      ]
    },
    {
      id: 2,
      name: 'Teamspace 2',
      projects: [
        {
          id: 3,
          name: 'Project 1',
          lists: [
            {
              id: 4,
              name: 'List 1',
              tasks: [
                { id: 8, name: 'Task 8' },
                { id: 9, name: 'Task 9' }
              ]
            }
          ]
        }
      ]
    }
  ]
} 