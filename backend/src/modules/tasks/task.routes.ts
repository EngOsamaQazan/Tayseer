import { Router } from 'express';
import { TaskController } from './task.controller';
import { authMiddleware } from '../../middleware/auth.middleware';
import { validateRequest } from '../../middleware/validation.middleware';
import { taskValidation } from './task.validation';

const router = Router();
const taskController = new TaskController();

// Apply authentication middleware to all routes
router.use(authMiddleware);

// Task routes
router.get('/', taskController.getAllTasks);
router.get('/:id', taskController.getTaskById);
router.post('/', validateRequest(taskValidation.createTask), taskController.createTask);
router.put('/:id', validateRequest(taskValidation.updateTask), taskController.updateTask);
router.delete('/:id', taskController.deleteTask);

// Task assignment routes
router.post('/:id/assign', validateRequest(taskValidation.assignTask), taskController.assignTask);
router.put('/:id/status', validateRequest(taskValidation.updateTaskStatus), taskController.updateTaskStatus);
router.get('/:id/comments', taskController.getTaskComments);
router.post('/:id/comments', validateRequest(taskValidation.addComment), taskController.addTaskComment);

// Task filters
router.get('/user/:userId', taskController.getTasksByUser);
router.get('/project/:projectId', taskController.getTasksByProject);
router.get('/status/:status', taskController.getTasksByStatus);

export default router;