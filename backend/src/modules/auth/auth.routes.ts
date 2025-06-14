import { Router } from 'express';
import { AuthController } from './auth.controller';
import {
  validateRegister,
  validateLogin,
  validateForgotPassword,
  validateResetPassword,
  validateChangePassword,
  validateVerifyEmail,
  validateResendOTP,
  validateUpdateProfile
} from './auth.validation';

const router = Router();

router.post(
  '/register',
  AuthController.register
);

router.post(
  '/login',
  AuthController.login
);

router.post(
  '/logout',
  AuthController.logout
);

router.post(
  '/refresh',
  AuthController.refreshToken
);

router.post(
  '/forgot-password',
  AuthController.forgotPassword
);

router.post(
  '/reset-password',
  AuthController.resetPassword
);

router.post(
  '/change-password',
  AuthController.changePassword
);

router.post(
  '/verify-email',
  AuthController.verifyEmail
);

router.post(
  '/resend-otp',
  AuthController.resendOTP
);

router.get(
  '/profile',
  AuthController.getProfile
);

router.put(
  '/profile',
  AuthController.updateProfile
);

export default router;
