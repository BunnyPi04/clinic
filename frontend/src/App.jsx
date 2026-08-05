import { Navigate, Route, Routes } from "react-router-dom";
import AppLayout from "./components/AppLayout";
import DashboardPage from "./pages/DashboardPage";
import PatientCreatePage from "./pages/PatientCreatePage";
import PatientDetailPage from "./pages/PatientDetailPage";
import PatientsPage from "./pages/PatientsPage";
import VisitsPage from "./pages/VisitsPage";
import VisitCreatePage from "./pages/visits/VisitCreatePage";
import VisitDetailPage from "./pages/visits/VisitDetailPage";
import VisitServicesPage from "./pages/visits/VisitServicesPage";
import ServiceCatalogPage from "./pages/services/ServiceCatalogPage";

export default function App() {
  return (
    <Routes>
      <Route element={<AppLayout />}>
        <Route index element={<DashboardPage />} />

        <Route path="patients">
          <Route index element={<PatientsPage />} />
          <Route path="create" element={<PatientCreatePage />} />
          <Route path=":patientId" element={<PatientDetailPage />} />
        </Route>

        <Route path="visits">
          <Route index element={<VisitsPage />} />
          <Route path="create" element={<VisitCreatePage />} />
          <Route path=":visitId/services" element={<VisitServicesPage />} />
          <Route path=":visitId" element={<VisitDetailPage />} />
        </Route>

        <Route
          path="service-catalogs"
          element={<ServiceCatalogPage />}
        />

        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  );
}
