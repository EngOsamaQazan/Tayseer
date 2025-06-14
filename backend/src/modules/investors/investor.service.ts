import { logger } from '../../config/logger';

interface Investor {
  id: number;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  nationalId: string;
  dateOfBirth: Date;
  address: string;
  investorType: 'individual' | 'corporate';
  riskProfile: 'conservative' | 'moderate' | 'aggressive';
  totalInvested: number;
  currentValue: number;
  status: 'active' | 'inactive' | 'suspended';
  createdAt: Date;
  updatedAt: Date;
}

interface Investment {
  id: number;
  investorId: number;
  investmentType: string;
  amount: number;
  purchaseDate: Date;
  currentValue: number;
  returns: number;
  status: 'active' | 'matured' | 'sold';
}

interface Portfolio {
  totalInvestments: number;
  totalValue: number;
  totalReturns: number;
  returnPercentage: number;
  investments: Investment[];
  assetAllocation: {
    stocks: number;
    bonds: number;
    realEstate: number;
    commodities: number;
    cash: number;
  };
}

interface InvestmentReturn {
  period: string;
  amount: number;
  percentage: number;
  date: Date;
}

export class InvestorService {
  // بيانات وهمية للمستثمرين
  private investors: Investor[] = [
    {
      id: 1,
      firstName: 'خالد',
      lastName: 'العلي',
      email: 'khalid.ali@example.com',
      phone: '+966501234567',
      nationalId: '1234567890',
      dateOfBirth: new Date('1985-05-15'),
      address: 'الرياض، المملكة العربية السعودية',
      investorType: 'individual',
      riskProfile: 'moderate',
      totalInvested: 500000,
      currentValue: 650000,
      status: 'active',
      createdAt: new Date('2023-01-01'),
      updatedAt: new Date()
    },
    {
      id: 2,
      firstName: 'نورا',
      lastName: 'المحمد',
      email: 'nora.mohammed@example.com',
      phone: '+966507654321',
      nationalId: '0987654321',
      dateOfBirth: new Date('1990-08-20'),
      address: 'جدة، المملكة العربية السعودية',
      investorType: 'individual',
      riskProfile: 'conservative',
      totalInvested: 200000,
      currentValue: 220000,
      status: 'active',
      createdAt: new Date('2023-02-15'),
      updatedAt: new Date()
    }
  ];

  // بيانات وهمية للاستثمارات
  private investments: Investment[] = [
    {
      id: 1,
      investorId: 1,
      investmentType: 'أسهم محلية',
      amount: 300000,
      purchaseDate: new Date('2023-01-15'),
      currentValue: 390000,
      returns: 90000,
      status: 'active'
    },
    {
      id: 2,
      investorId: 1,
      investmentType: 'صكوك',
      amount: 200000,
      purchaseDate: new Date('2023-03-01'),
      currentValue: 260000,
      returns: 60000,
      status: 'active'
    },
    {
      id: 3,
      investorId: 2,
      investmentType: 'صناديق مؤشرة',
      amount: 200000,
      purchaseDate: new Date('2023-02-20'),
      currentValue: 220000,
      returns: 20000,
      status: 'active'
    }
  ];

  async getAllInvestors(): Promise<Investor[]> {
    try {
      logger.info('جلب قائمة جميع المستثمرين');
      return this.investors;
    } catch (error) {
      logger.error('خطأ في جلب قائمة المستثمرين:', error);
      throw error;
    }
  }

  async getInvestorById(id: number): Promise<Investor | null> {
    try {
      logger.info(`جلب بيانات المستثمر برقم: ${id}`);
      const investor = this.investors.find(inv => inv.id === id);
      return investor || null;
    } catch (error) {
      logger.error(`خطأ في جلب بيانات المستثمر برقم ${id}:`, error);
      throw error;
    }
  }

  async createInvestor(investorData: Partial<Investor>): Promise<Investor> {
    try {
      logger.info('إنشاء مستثمر جديد');
      const newInvestor: Investor = {
        id: Math.max(...this.investors.map(i => i.id), 0) + 1,
        firstName: investorData.firstName || '',
        lastName: investorData.lastName || '',
        email: investorData.email || '',
        phone: investorData.phone || '',
        nationalId: investorData.nationalId || '',
        dateOfBirth: investorData.dateOfBirth || new Date(),
        address: investorData.address || '',
        investorType: investorData.investorType || 'individual',
        riskProfile: investorData.riskProfile || 'moderate',
        totalInvested: 0,
        currentValue: 0,
        status: investorData.status || 'active',
        createdAt: new Date(),
        updatedAt: new Date()
      };
      
      this.investors.push(newInvestor);
      return newInvestor;
    } catch (error) {
      logger.error('خطأ في إنشاء المستثمر:', error);
      throw error;
    }
  }

  async updateInvestor(id: number, updateData: Partial<Investor>): Promise<Investor | null> {
    try {
      logger.info(`تحديث بيانات المستثمر برقم: ${id}`);
      const investorIndex = this.investors.findIndex(inv => inv.id === id);
      
      if (investorIndex === -1) {
        return null;
      }
      
      this.investors[investorIndex] = {
        ...this.investors[investorIndex],
        ...updateData,
        updatedAt: new Date()
      };
      
      return this.investors[investorIndex];
    } catch (error) {
      logger.error(`خطأ في تحديث بيانات المستثمر برقم ${id}:`, error);
      throw error;
    }
  }

  async deleteInvestor(id: number): Promise<void> {
    try {
      logger.info(`حذف المستثمر برقم: ${id}`);
      const investorIndex = this.investors.findIndex(inv => inv.id === id);
      
      if (investorIndex !== -1) {
        this.investors.splice(investorIndex, 1);
      }
    } catch (error) {
      logger.error(`خطأ في حذف المستثمر برقم ${id}:`, error);
      throw error;
    }
  }

  async getInvestorInvestments(investorId: number): Promise<Investment[]> {
    try {
      logger.info(`جلب استثمارات المستثمر برقم: ${investorId}`);
      return this.investments.filter(inv => inv.investorId === investorId);
    } catch (error) {
      logger.error(`خطأ في جلب استثمارات المستثمر برقم ${investorId}:`, error);
      throw error;
    }
  }

  async createInvestment(investorId: number, investmentData: any): Promise<Investment> {
    try {
      logger.info(`إنشاء استثمار جديد للمستثمر برقم: ${investorId}`);
      const newInvestment: Investment = {
        id: Math.max(...this.investments.map(i => i.id), 0) + 1,
        investorId,
        investmentType: investmentData.investmentType,
        amount: investmentData.amount,
        purchaseDate: new Date(investmentData.purchaseDate || Date.now()),
        currentValue: investmentData.amount, // نفس المبلغ في البداية
        returns: 0, // لا توجد عوائد في البداية
        status: 'active'
      };
      
      this.investments.push(newInvestment);
      
      // تحديث إجمالي استثمارات المستثمر
      const investor = this.investors.find(inv => inv.id === investorId);
      if (investor) {
        investor.totalInvested += newInvestment.amount;
        investor.currentValue += newInvestment.currentValue;
        investor.updatedAt = new Date();
      }
      
      return newInvestment;
    } catch (error) {
      logger.error(`خطأ في إنشاء استثمار للمستثمر برقم ${investorId}:`, error);
      throw error;
    }
  }

  async getInvestorPortfolio(investorId: number): Promise<Portfolio> {
    try {
      logger.info(`جلب محفظة المستثمر برقم: ${investorId}`);
      const investments = await this.getInvestorInvestments(investorId);
      
      const totalInvestments = investments.reduce((sum, inv) => sum + inv.amount, 0);
      const totalValue = investments.reduce((sum, inv) => sum + inv.currentValue, 0);
      const totalReturns = investments.reduce((sum, inv) => sum + inv.returns, 0);
      const returnPercentage = totalInvestments > 0 ? (totalReturns / totalInvestments) * 100 : 0;
      
      // توزيع الأصول (بيانات وهمية)
      const portfolio: Portfolio = {
        totalInvestments,
        totalValue,
        totalReturns,
        returnPercentage,
        investments,
        assetAllocation: {
          stocks: 40,
          bonds: 30,
          realEstate: 20,
          commodities: 5,
          cash: 5
        }
      };
      
      return portfolio;
    } catch (error) {
      logger.error(`خطأ في جلب محفظة المستثمر برقم ${investorId}:`, error);
      throw error;
    }
  }

  async getInvestorReturns(investorId: number): Promise<InvestmentReturn[]> {
    try {
      logger.info(`جلب عوائد المستثمر برقم: ${investorId}`);
      // بيانات وهمية للعوائد
      return [
        {
          period: '2024-01',
          amount: 15000,
          percentage: 3.5,
          date: new Date('2024-01-31')
        },
        {
          period: '2024-02',
          amount: 18000,
          percentage: 4.2,
          date: new Date('2024-02-29')
        },
        {
          period: '2024-03',
          amount: 12000,
          percentage: 2.8,
          date: new Date('2024-03-31')
        }
      ];
    } catch (error) {
      logger.error(`خطأ في جلب عوائد المستثمر برقم ${investorId}:`, error);
      throw error;
    }
  }
}