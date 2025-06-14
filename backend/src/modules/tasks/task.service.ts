export interface Task {
  id: string;
  title: string;
  description: string;
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  assigneeId?: string;
  projectId?: string;
  dueDate?: Date;
  createdAt: Date;
  updatedAt: Date;
  estimatedHours?: number;
  actualHours?: number;
  tags?: string[];
}

export interface TaskComment {
  id: string;
  taskId: string;
  userId: string;
  content: string;
  createdAt: Date;
}

export interface TaskFilters {
  status?: string;
  priority?: string;
  assignee?: string;
}

export class TaskService {
  private tasks: Task[] = [
    {
      id: '1',
      title: 'تطوير واجهة المستخدم الرئيسية',
      description: 'إنشاء الصفحة الرئيسية للتطبيق مع التصميم المطلوب',
      status: 'in_progress',
      priority: 'high',
      assigneeId: 'emp1',
      projectId: 'proj1',
      dueDate: new Date('2024-02-01'),
      createdAt: new Date('2024-01-15'),
      updatedAt: new Date('2024-01-20'),
      estimatedHours: 40,
      actualHours: 20,
      tags: ['frontend', 'ui/ux']
    },
    {
      id: '2',
      title: 'إعداد قاعدة البيانات',
      description: 'تصميم وإنشاء جداول قاعدة البيانات الأساسية',
      status: 'completed',
      priority: 'high',
      assigneeId: 'emp2',
      projectId: 'proj1',
      dueDate: new Date('2024-01-25'),
      createdAt: new Date('2024-01-10'),
      updatedAt: new Date('2024-01-22'),
      estimatedHours: 16,
      actualHours: 18,
      tags: ['backend', 'database']
    }
  ];

  private comments: TaskComment[] = [
    {
      id: '1',
      taskId: '1',
      userId: 'emp1',
      content: 'بدأت العمل على التصميم الأولي',
      createdAt: new Date('2024-01-16')
    }
  ];

  async getAllTasks(page: number = 1, limit: number = 10, filters: TaskFilters = {}): Promise<{
    tasks: Task[];
    total: number;
    page: number;
    totalPages: number;
  }> {
    let filteredTasks = [...this.tasks];

    // Apply filters
    if (filters.status) {
      filteredTasks = filteredTasks.filter(task => task.status === filters.status);
    }
    if (filters.priority) {
      filteredTasks = filteredTasks.filter(task => task.priority === filters.priority);
    }
    if (filters.assignee) {
      filteredTasks = filteredTasks.filter(task => task.assigneeId === filters.assignee);
    }

    const total = filteredTasks.length;
    const totalPages = Math.ceil(total / limit);
    const startIndex = (page - 1) * limit;
    const tasks = filteredTasks.slice(startIndex, startIndex + limit);

    return {
      tasks,
      total,
      page,
      totalPages
    };
  }

  async getTaskById(id: string): Promise<Task | null> {
    return this.tasks.find(task => task.id === id) || null;
  }

  async createTask(taskData: Partial<Task>): Promise<Task> {
    const newTask: Task = {
      id: (this.tasks.length + 1).toString(),
      title: taskData.title || '',
      description: taskData.description || '',
      status: taskData.status || 'pending',
      priority: taskData.priority || 'medium',
      assigneeId: taskData.assigneeId,
      projectId: taskData.projectId,
      dueDate: taskData.dueDate,
      createdAt: new Date(),
      updatedAt: new Date(),
      estimatedHours: taskData.estimatedHours,
      actualHours: taskData.actualHours || 0,
      tags: taskData.tags || []
    };

    this.tasks.push(newTask);
    return newTask;
  }

  async updateTask(id: string, updateData: Partial<Task>): Promise<Task | null> {
    const taskIndex = this.tasks.findIndex(task => task.id === id);
    if (taskIndex === -1) {
      return null;
    }

    this.tasks[taskIndex] = {
      ...this.tasks[taskIndex],
      ...updateData,
      updatedAt: new Date()
    };

    return this.tasks[taskIndex];
  }

  async deleteTask(id: string): Promise<boolean> {
    const taskIndex = this.tasks.findIndex(task => task.id === id);
    if (taskIndex === -1) {
      return false;
    }

    this.tasks.splice(taskIndex, 1);
    // Also remove related comments
    this.comments = this.comments.filter(comment => comment.taskId !== id);
    return true;
  }

  async assignTask(taskId: string, assigneeId: string): Promise<Task | null> {
    return this.updateTask(taskId, { assigneeId });
  }

  async updateTaskStatus(taskId: string, status: Task['status']): Promise<Task | null> {
    return this.updateTask(taskId, { status });
  }

  async getTaskComments(taskId: string): Promise<TaskComment[]> {
    return this.comments.filter(comment => comment.taskId === taskId);
  }

  async addTaskComment(taskId: string, commentData: {
    userId: string;
    content: string;
  }): Promise<TaskComment> {
    const newComment: TaskComment = {
      id: (this.comments.length + 1).toString(),
      taskId,
      userId: commentData.userId,
      content: commentData.content,
      createdAt: new Date()
    };

    this.comments.push(newComment);
    return newComment;
  }

  async getTasksByUser(userId: string): Promise<Task[]> {
    return this.tasks.filter(task => task.assigneeId === userId);
  }

  async getTasksByProject(projectId: string): Promise<Task[]> {
    return this.tasks.filter(task => task.projectId === projectId);
  }

  async getTasksByStatus(status: string): Promise<Task[]> {
    return this.tasks.filter(task => task.status === status);
  }
}