import { Request, Response } from 'express';
import { TaskService } from './task.service';
import { logger } from '../../config/logger';

export class TaskController {
  private taskService: TaskService;

  constructor() {
    this.taskService = new TaskService();
  }

  getAllTasks = async (req: Request, res: Response): Promise<void> => {
    try {
      const { page = 1, limit = 10, status, priority, assignee } = req.query;
      const filters = { status, priority, assignee };
      const tasks = await this.taskService.getAllTasks(
        Number(page),
        Number(limit),
        filters
      );
      res.json({
        success: true,
        data: tasks,
        message: 'تم جلب المهام بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching tasks:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب المهام'
      });
    }
  };

  getTaskById = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const task = await this.taskService.getTaskById(id);
      if (!task) {
        res.status(404).json({
          success: false,
          message: 'المهمة غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: task,
        message: 'تم جلب المهمة بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching task:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب المهمة'
      });
    }
  };

  createTask = async (req: Request, res: Response): Promise<void> => {
    try {
      const taskData = req.body;
      const newTask = await this.taskService.createTask(taskData);
      res.status(201).json({
        success: true,
        data: newTask,
        message: 'تم إنشاء المهمة بنجاح'
      });
    } catch (error) {
      logger.error('Error creating task:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في إنشاء المهمة'
      });
    }
  };

  updateTask = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const updateData = req.body;
      const updatedTask = await this.taskService.updateTask(id, updateData);
      if (!updatedTask) {
        res.status(404).json({
          success: false,
          message: 'المهمة غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: updatedTask,
        message: 'تم تحديث المهمة بنجاح'
      });
    } catch (error) {
      logger.error('Error updating task:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تحديث المهمة'
      });
    }
  };

  deleteTask = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const deleted = await this.taskService.deleteTask(id);
      if (!deleted) {
        res.status(404).json({
          success: false,
          message: 'المهمة غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        message: 'تم حذف المهمة بنجاح'
      });
    } catch (error) {
      logger.error('Error deleting task:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في حذف المهمة'
      });
    }
  };

  assignTask = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const { assigneeId } = req.body;
      const assignedTask = await this.taskService.assignTask(id, assigneeId);
      if (!assignedTask) {
        res.status(404).json({
          success: false,
          message: 'المهمة غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: assignedTask,
        message: 'تم تعيين المهمة بنجاح'
      });
    } catch (error) {
      logger.error('Error assigning task:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تعيين المهمة'
      });
    }
  };

  updateTaskStatus = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const { status } = req.body;
      const updatedTask = await this.taskService.updateTaskStatus(id, status);
      if (!updatedTask) {
        res.status(404).json({
          success: false,
          message: 'المهمة غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: updatedTask,
        message: 'تم تحديث حالة المهمة بنجاح'
      });
    } catch (error) {
      logger.error('Error updating task status:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تحديث حالة المهمة'
      });
    }
  };

  getTaskComments = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const comments = await this.taskService.getTaskComments(id);
      res.json({
        success: true,
        data: comments,
        message: 'تم جلب تعليقات المهمة بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching task comments:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب تعليقات المهمة'
      });
    }
  };

  addTaskComment = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const commentData = req.body;
      const comment = await this.taskService.addTaskComment(id, commentData);
      res.status(201).json({
        success: true,
        data: comment,
        message: 'تم إضافة التعليق بنجاح'
      });
    } catch (error) {
      logger.error('Error adding task comment:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في إضافة التعليق'
      });
    }
  };

  getTasksByUser = async (req: Request, res: Response): Promise<void> => {
    try {
      const { userId } = req.params;
      const tasks = await this.taskService.getTasksByUser(userId);
      res.json({
        success: true,
        data: tasks,
        message: 'تم جلب مهام المستخدم بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching user tasks:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب مهام المستخدم'
      });
    }
  };

  getTasksByProject = async (req: Request, res: Response): Promise<void> => {
    try {
      const { projectId } = req.params;
      const tasks = await this.taskService.getTasksByProject(projectId);
      res.json({
        success: true,
        data: tasks,
        message: 'تم جلب مهام المشروع بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching project tasks:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب مهام المشروع'
      });
    }
  };

  getTasksByStatus = async (req: Request, res: Response): Promise<void> => {
    try {
      const { status } = req.params;
      const tasks = await this.taskService.getTasksByStatus(status);
      res.json({
        success: true,
        data: tasks,
        message: 'تم جلب المهام بالحالة المحددة بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching tasks by status:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب المهام بالحالة المحددة'
      });
    }
  };
}