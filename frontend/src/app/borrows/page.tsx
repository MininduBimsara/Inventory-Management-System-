"use client";

import { AppShell } from "@/components/AppShell";
import { RequireAuth } from "@/components/RequireAuth";
import { apiRequest } from "@/lib/api";
import { formatDateTime, toErrorMessage } from "@/lib/format";
import { useCallback, useEffect, useState } from "react";

type Item = { id: number; name: string; code: string };
type BorrowLine = {
  id: number;
  quantity_borrowed: number;
  quantity_returned: number;
  quantity_pending: number;
};
type Borrow = {
  id: number;
  borrower_name: string;
  status: string;
  created_at: string;
  items: BorrowLine[];
};

type CollectionResponse<T> = { data: T[] };

const today = new Date().toISOString().slice(0, 10);

export default function BorrowsPage(): React.ReactElement {
  const [items, setItems] = useState<Item[]>([]);
  const [borrows, setBorrows] = useState<Borrow[]>([]);
  const [borrowerName, setBorrowerName] = useState("John Doe");
  const [borrowerContact, setBorrowerContact] = useState("0771234567");
  const [borrowDate, setBorrowDate] = useState(today);
  const [expectedReturnDate, setExpectedReturnDate] = useState(today);
  const [itemId, setItemId] = useState<number>(0);
  const [borrowQty, setBorrowQty] = useState(1);
  const [returnCondition, setReturnCondition] = useState("good");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const loadData = useCallback(async (): Promise<void> => {
    try {
      const [itemsResponse, borrowsResponse] = await Promise.all([
        apiRequest<CollectionResponse<Item>>("/items"),
        apiRequest<CollectionResponse<Borrow>>("/borrows"),
      ]);

      setItems(itemsResponse.data);
      setBorrows(borrowsResponse.data);

      if (itemsResponse.data.length > 0 && itemId === 0) {
        setItemId(itemsResponse.data[0].id);
      }
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    }
  }, [itemId]);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    void loadData();
  }, [loadData]);

  const handleCreateBorrow = async (
    event: React.FormEvent<HTMLFormElement>,
  ): Promise<void> => {
    event.preventDefault();
    setError(null);
    setMessage(null);

    try {
      await apiRequest("/borrows", {
        method: "POST",
        body: JSON.stringify({
          borrower_name: borrowerName,
          borrower_contact: borrowerContact,
          borrow_date: borrowDate,
          expected_return_date: expectedReturnDate,
          items: [{ item_id: itemId, quantity_borrowed: borrowQty }],
        }),
      });

      setMessage("Borrow transaction created.");
      await loadData();
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    }
  };

  const handleReturnFirstPending = async (borrow: Borrow): Promise<void> => {
    const firstPending = borrow.items.find((line) => line.quantity_pending > 0);
    if (!firstPending) {
      setError("No pending lines for return.");
      return;
    }

    setError(null);
    setMessage(null);

    try {
      await apiRequest(`/borrows/${borrow.id}/return`, {
        method: "POST",
        body: JSON.stringify({
          items: [
            {
              borrow_transaction_item_id: firstPending.id,
              quantity_returned: 1,
              item_condition_on_return: returnCondition,
            },
          ],
        }),
      });

      setMessage(`Return recorded for borrow #${borrow.id}.`);
      await loadData();
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    }
  };

  return (
    <RequireAuth>
      <AppShell
        title="Borrow and Return Management"
        subtitle="Create borrow transactions and process returns"
      >
        {message ? <p className="success-text">{message}</p> : null}
        {error ? <p className="error-text">{error}</p> : null}

        <form className="panel-card form-grid" onSubmit={handleCreateBorrow}>
          <h3>Create Borrow</h3>
          <input
            value={borrowerName}
            onChange={(event) => setBorrowerName(event.target.value)}
            required
          />
          <input
            value={borrowerContact}
            onChange={(event) => setBorrowerContact(event.target.value)}
            required
          />
          <input
            type="date"
            value={borrowDate}
            onChange={(event) => setBorrowDate(event.target.value)}
            required
          />
          <input
            type="date"
            value={expectedReturnDate}
            onChange={(event) => setExpectedReturnDate(event.target.value)}
            required
          />
          <select
            value={itemId}
            onChange={(event) => setItemId(Number(event.target.value))}
            required
          >
            {items.map((item) => (
              <option key={item.id} value={item.id}>
                {item.name} ({item.code})
              </option>
            ))}
          </select>
          <input
            type="number"
            min={1}
            value={borrowQty}
            onChange={(event) => setBorrowQty(Number(event.target.value))}
            required
          />
          <button type="submit">Create Borrow Transaction</button>
        </form>

        <div className="panel-card form-grid">
          <h3>Return Condition</h3>
          <select
            value={returnCondition}
            onChange={(event) => setReturnCondition(event.target.value)}
          >
            <option value="good">good</option>
            <option value="damaged">damaged</option>
            <option value="missing">missing</option>
          </select>
          <p className="muted-text">
            Use the button in each borrow row to return one pending quantity.
          </p>
        </div>

        <div className="panel-card">
          <h3>Borrows</h3>
          <table className="table-grid">
            <thead>
              <tr>
                <th>ID</th>
                <th>Borrower</th>
                <th>Status</th>
                <th>Created</th>
                <th>Pending</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              {borrows.map((borrow) => {
                const pending = borrow.items.reduce(
                  (sum, line) => sum + line.quantity_pending,
                  0,
                );

                return (
                  <tr key={borrow.id}>
                    <td>{borrow.id}</td>
                    <td>{borrow.borrower_name}</td>
                    <td>{borrow.status}</td>
                    <td>{formatDateTime(borrow.created_at)}</td>
                    <td>{pending}</td>
                    <td>
                      <button
                        type="button"
                        onClick={() => void handleReturnFirstPending(borrow)}
                      >
                        Return 1 Unit
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </AppShell>
    </RequireAuth>
  );
}
