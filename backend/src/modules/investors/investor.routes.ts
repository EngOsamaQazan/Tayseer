import { Router } from 'express';
import { InvestorController } from './investor.controller';
import { authMiddleware } from '../../middleware/auth.middleware';
import { validate } from '../../middleware/validation.middleware';
import { investorValidation } from './investor.validation';

const router = Router();
const investorController = new InvestorController();

// جميع المسارات تتطلب المصادقة
router.use(authMiddleware);

// مسارات المستثمرين
router.get('/', investorController.getAllInvestors.bind(investorController));
router.get('/:id', investorController.getInvestorById.bind(investorController));
router.post('/', validate(investorValidation.createInvestor), investorController.createInvestor.bind(investorController));
router.put('/:id', validate(investorValidation.updateInvestor), investorController.updateInvestor.bind(investorController));
router.delete('/:id', investorController.deleteInvestor.bind(investorController));

// مسارات الاستثمارات
router.get('/:id/investments', investorController.getInvestorInvestments.bind(investorController));
router.post('/:id/investments', validate(investorValidation.createInvestment), investorController.createInvestment.bind(investorController));
router.get('/:id/portfolio', investorController.getInvestorPortfolio.bind(investorController));
router.get('/:id/returns', investorController.getInvestorReturns.bind(investorController));

export default router;