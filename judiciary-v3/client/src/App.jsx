import { Routes, Route, Navigate } from 'react-router-dom';
import Layout from './components/Layout';

import CasesTab from './pages/CasesTab';
import ActionsTab from './pages/ActionsTab';
import PersistenceTab from './pages/PersistenceTab';
import LegalTab from './pages/LegalTab';
import CollectionTab from './pages/CollectionTab';
import CaseView from './pages/CaseView';
import CaseForm from './pages/CaseForm';
import Timeline from './pages/Timeline';
import BatchCases from './pages/BatchCases';
import BatchActions from './pages/BatchActions';
import DeadlineDashboard from './pages/DeadlineDashboard';

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<Navigate to="/cases" replace />} />
        <Route path="cases" element={<CasesTab />} />
        <Route path="actions" element={<ActionsTab />} />
        <Route path="persistence" element={<PersistenceTab />} />
        <Route path="legal" element={<LegalTab />} />
        <Route path="collection" element={<CollectionTab />} />
        <Route path="case/new" element={<CaseForm />} />
        <Route path="case/:id" element={<CaseView />} />
        <Route path="case/:id/edit" element={<CaseForm />} />
        <Route path="case/:id/timeline" element={<Timeline />} />
        <Route path="batch/cases" element={<BatchCases />} />
        <Route path="batch/actions" element={<BatchActions />} />
        <Route path="deadlines" element={<DeadlineDashboard />} />
      </Route>
    </Routes>
  );
}
